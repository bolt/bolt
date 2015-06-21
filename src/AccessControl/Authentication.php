<?php
namespace Bolt\AccessControl;

use Bolt\Application;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\DBALException;
use Hautelook\Phpass\PasswordHash;
use Symfony\Component\HttpFoundation\Request;
use UAParser;

/**
 * Authentication handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Authentication
{
    /** @var \Silex\Application $app */
    private $app;
    /** @var boolean */
    private $validsession;
    /** @var integer */
    private $hashStrength;

    /** @var string */
    private $remoteIP;
    /** @var string */
    private $userAgent;
    /** @var string */
    private $hostName;
    /** @var string */
    private $authToken;
    /** @var \Bolt\Storage\Repository\AuthtokenRepository $repository */
    private $repository;

    public function __construct(Application $app, Repository\AuthtokenRepository $repository)
    {
        $this->app = $app;
        $this->repository = $repository;

        // Hashstrength has a default of '10', don't allow less than '8'.
        $this->hashStrength = max($this->app['config']->get('general/hash_strength'), 8);

        // Handle broken request set up
        $this->setRequest();
    }

    /**
     * Get the IP stored earlier in the request cycle. If it's missing we're on
     * the CLI, so use localhost.
     *
     * @deprecated to be removed in Bolt 3.0
     *
     * @see discussion in https://github.com/bolt/bolt/pull/3031
     */
    private function setRequest()
    {
        $request = Request::createFromGlobals();
        $this->hostName  = $request->getHost();
        $this->remoteIP  = $request->getClientIp() ?: '127.0.0.1';
        $this->userAgent = $request->server->get('HTTP_USER_AGENT');
        $this->authToken = $request->cookies->get($this->app['token.authentication.name']);
    }

    /**
     * Check if a given token matches the current (correct) Anit-CSRF-like token.
     *
     * @param string $token
     *
     * @return boolean
     */
    public function checkAntiCSRFToken($token = '')
    {
        if (empty($token)) {
            $token = $this->app['request']->get('bolt_csrf_token');
        }

        if ($token === $this->getAntiCSRFToken()) {
            return true;
        } else {
            $this->app['logger.flash']->error('The security token was incorrect. Please try again.');

            return false;
        }
    }

    /**
     * We will not allow tampering with sessions, so we make sure the current session
     * is still valid for the device on which it was created, and that the username,
     * ip-address are still the same.
     *
     * @return boolean|string
     */
    public function checkValidSession()
    {
        if ($this->app['session']->isStarted() && $currentuser = $this->app['session']->get('user')) {
            $this->app['users']->setCurrentUser($currentuser);

            if ($database = $this->app['users']->getUser($currentuser['id'])) {
                // Update the session with the user from the database.
                $this->app['users']->setCurrentUser(array_merge($currentuser, $database));
            } else {
                // User doesn't exist anymore
                $this->logout();

                return false;
            }
            if (!$currentuser['enabled']) {
                // User has been disabled since logging in
                $this->logout();

                return false;
            }
        } else {
            // No current user, check if we can resume from authtoken cookie, or
            // return without doing the rest.
            return $this->loginAuthtoken();
        }

        // The auth token is based on hostname, IP and browser user agent
        $key = $this->getAuthToken($currentuser['username']);

        if ($key != $currentuser['sessionkey']) {
            $this->app['logger.system']->error("Keys don't match. Invalidating session: $key != " . $currentuser['sessionkey'], ['event' => 'authentication']);
            $this->app['logger.system']->info("Automatically logged out user '" . $currentuser['username'] . "': Session data didn't match.", ['event' => 'authentication']);
            $this->logout();

            return false;
        }

        // Check if user is _still_ allowed to log on.
        if (!$this->app['users']->isAllowed('login') || !$currentuser['enabled']) {
            $this->logout();

            return false;
        }

        // Check if there's a bolt_authtoken cookie. If not, set it.
        if (empty($this->authToken)) {
            $this->setAuthtoken();
        }

        return true;
    }

    /**
     * Lookup active sessions.
     *
     * @return array
     */
    public function getActiveSessions()
    {
        $this->deleteExpiredSessions();

        $sessions = $this->repository->getActiveSessions() ?: [];


        // Parse the user-agents to get a user-friendly Browser, version and platform.
        $parser = UAParser\Parser::create();

        foreach ($sessions as $key => $session) {
            $ua = $parser->parse($session['useragent']);
            $sessions[$key]['browser'] = sprintf('%s / %s', $ua->ua->toString(), $ua->os->toString());
        }

        return $sessions;
    }

    /**
     * Generate a Anti-CSRF-like token, to use in GET requests for stuff that ought to be POST-ed forms.
     *
     * @return string
     */
    public function getAntiCSRFToken()
    {
        $seed = $this->app['request']->cookies->get($this->app['token.session.name']);

        if ($this->app['config']->get('general/cookies_use_remoteaddr')) {
            $seed .= '-' . $this->remoteIP;
        }
        if ($this->app['config']->get('general/cookies_use_browseragent')) {
            $seed .= '-' . $this->userAgent;
        }
        if ($this->app['config']->get('general/cookies_use_httphost')) {
            $seed .= '-' . $this->hostName;
        }

        $token = substr(md5($seed), 0, 8);

        return $token;
    }

    /**
     * Return whether or not the current session is valid.
     *
     * @return boolean|string
     */
    public function isValidSession()
    {
        if ($this->validsession === null) {
            // Set 'validsession', to see if the current session is valid.
            $this->validsession = $this->checkValidSession();
        }

        return $this->validsession;
    }

    /**
     * Attempt to login a user with the given password. Accepts username or email.
     *
     * @param string $user
     * @param string $password
     *
     * @return boolean|string
     */
    public function login($user, $password)
    {
        $repository = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users');
        if (!$userEntity = $repository->getUser($user)) {
            $this->app['logger.flash']->error(Trans::__('Your account is disabled. Sorry about that.'));

            return false;
        }

        if ((int) $userEntity->getEnabled() !== 1) {
            $this->app['logger.flash']->error(Trans::__('Your account is disabled. Sorry about that.'));

            return false;
        }

        $hasher = new PasswordHash($this->hashStrength, true);
        if (!$hasher->CheckPassword($password, $userEntity->getPassword())) {
            return $this->loginFailed($user);
        }

        $this->app['users']->setCurrentUser($userEntity);
        $this->updateUserLogin($userEntity);

        return $this->setAuthToken();
    }

    /**
     * Attempt to login a user via the bolt_authtoken cookie.
     *
     * @return boolean|string
     */
    public function loginAuthtoken()
    {
        // If there's no cookie, we can't resume a session from the authtoken.
        if (empty($this->authToken)) {
            return false;
        }

        $authtoken = $this->authToken;
        $remoteip  = $this->remoteIP;
        $browser   = $this->userAgent;

        $this->deleteExpiredSessions();

        // Check if there's already a token stored for this token / IP combo.
        $row = $this->repository->getToken($authtoken, $remoteip, $browser);

        // If there's no row, we can't resume a session from the authtoken.
        if (empty($row)) {
            return false;
        }

        $checksalt = $this->getAuthToken($row['username'], $row['salt']);

        if ($checksalt === $row['token']) {
            $user = $this->app['users']->getUser($row['username']);

            $update = [
                'lastseen'       => date('Y-m-d H:i:s'),
                'lastip'         => $this->remoteIP,
                'failedlogins'   => 0,
                'throttleduntil' => $this->throttleUntil(0)
            ];

            // Attempt to update the last login, but don't break on failure.
            try {
                $this->app['db']->update($this->getTableName('users'), $update, ['id' => $user['id']]);
            } catch (DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            }

            $user['sessionkey'] = $this->getAuthToken($user['username']);

            $this->app['session']->set('user', $user);
            $this->app['logger.flash']->success(Trans::__('Session resumed.'));

            $this->app['users']->setCurrentUser($user);

            return $this->setAuthToken();
        } else {
            // Implementation note:
            // This needs to be caught in the controller and the authtoken
            // cookie deleted: $response->headers->clearCookie($this->app['token.authentication.name']);
            return false;
        }
    }

    /**
     * Log out the currently logged in user.
     */
    public function logout()
    {
        $this->app['logger.flash']->info(Trans::__('You have been logged out.'));
        $this->app['session']->remove('user');
        $this->app['session']->migrate(true);

        // Remove all auth tokens when logging off a user (so we sign out _all_ this user's sessions on all locations)
        $this->repository->deleteTokens($this->app['users']->getCurrentUserProperty('username'));
    }

    /**
     * Set a random password for user.
     *
     * @param string $username User specified by ID, username or email address.
     *
     * @return string|boolean New password or FALSE when no match for username.
     */
    public function setRandomPassword($username)
    {
        $password = false;
        $user = $this->app['users']->getUser($username);

        if (!empty($user)) {
            $password = $this->app['randomgenerator']->generateString(12);

            $hasher = new PasswordHash($this->hashStrength, true);
            $hashedpassword = $hasher->HashPassword($password);

            $update = [
                'password'       => $hashedpassword,
                'shadowpassword' => '',
                'shadowtoken'    => '',
                'shadowvalidity' => null
            ];

            $this->app['db']->update($this->getTableName('users'), $update, ['id' => $user['id']]);

            $this->app['logger.system']->info(
                "Password for user '{$user['username']}' was reset via Nut.",
                ['event' => 'authentication']
            );
        }

        return $password;
    }

    /**
     * Handle a password reset confirmation
     *
     * @param string $token
     *
     * @return void
     */
    public function resetPasswordConfirm($token)
    {
        $token .= '-' . str_replace('.', '-', $this->remoteIP);

        $now = date('Y-m-d H:i:s');

        // Let's see if the token is valid, and it's been requested within two hours.
        $query = sprintf('SELECT * FROM %s WHERE shadowtoken = ? AND shadowvalidity > ?', $this->getTableName('users'));
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
        $user = $this->app['db']->executeQuery($query, [$token, $now], [\PDO::PARAM_STR])->fetch();

        if (!empty($user)) {

            // allright, we can reset this user.
            $this->app['logger.flash']->success(Trans::__('Password reset successful! You can now log on with the password that was sent to you via email.'));

            $update = [
                'password'       => $user['shadowpassword'],
                'shadowpassword' => '',
                'shadowtoken'    => '',
                'shadowvalidity' => null
            ];
            $this->app['db']->update($this->getTableName('users'), $update, ['id' => $user['id']]);
        } else {

            // That was not a valid token, or too late, or not from the correct IP.
            $this->app['logger.system']->error('Somebody tried to reset a password with an invalid token.', ['event' => 'authentication']);
            $this->app['logger.flash']->error(Trans::__('Password reset not successful! Either the token was incorrect, or you were too late, or you tried to reset the password from a different IP-address.'));
        }
    }

    /**
     * Sends email with password request. Accepts email or username
     *
     * @param string $username
     *
     * @return boolean
     */
    public function resetPasswordRequest($username)
    {
        $user = $this->app['users']->getUser($username);

        $recipients = false;

        if (!empty($user)) {
            $shadowpassword = $this->app['randomgenerator']->generateString(12);
            $shadowtoken = $this->app['randomgenerator']->generateString(32);

            $hasher = new PasswordHash($this->hashStrength, true);
            $shadowhashed = $hasher->HashPassword($shadowpassword);

            $shadowlink = sprintf(
                '%s%sresetpassword?token=%s',
                $this->app['resources']->getUrl('hosturl'),
                $this->app['resources']->getUrl('bolt'),
                urlencode($shadowtoken)
            );

            // Set the shadow password and related stuff in the database.
            $update = [
                'shadowpassword' => $shadowhashed,
                'shadowtoken'    => $shadowtoken . '-' . str_replace('.', '-', $this->remoteIP),
                'shadowvalidity' => date('Y-m-d H:i:s', strtotime('+2 hours'))
            ];
            $this->app['db']->update($this->getTableName('users'), $update, ['id' => $user['id']]);

            // Compile the email with the shadow password and reset link.
            $mailhtml = $this->app['render']->render(
                'mail/passwordreset.twig',
                [
                    'user'           => $user,
                    'shadowpassword' => $shadowpassword,
                    'shadowtoken'    => $shadowtoken,
                    'shadowvalidity' => date('Y-m-d H:i:s', strtotime('+2 hours')),
                    'shadowlink'     => $shadowlink
                ]
            );

            $subject = sprintf('[ Bolt / %s ] Password reset.', $this->app['config']->get('general/sitename'));

            $message = $this->app['mailer']
                ->createMessage('message')
                ->setSubject($subject)
                ->setFrom([$this->app['config']->get('general/mailoptions/senderMail', $user['email']) => $this->app['config']->get('general/mailoptions/senderName', $this->app['config']->get('general/sitename'))])
                ->setTo([$user['email'] => $user['displayname']])
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html');

            $recipients = $this->app['mailer']->send($message);

            if ($recipients) {
                $this->app['logger.system']->info("Password request sent to '" . $user['displayname'] . "'.", ['event' => 'authentication']);
            } else {
                $this->app['logger.system']->error("Failed to send password request sent to '" . $user['displayname'] . "'.", ['event' => 'authentication']);
                $this->app['logger.flash']->error(Trans::__("Failed to send password request. Please check the email settings."));
            }
        }

        // For safety, this is the message we display, regardless of whether $user exists.
        if ($recipients === false || $recipients > 0) {
            $this->app['logger.flash']->info(Trans::__("A password reset link has been sent to '%user%'.", ['%user%' => $username]));
        }

        return true;
    }

    /**
     * Update the user record with latest login information.
     *
     * @param Entity\Users $user
     */
    protected function updateUserLogin(Entity\Users $user)
    {
        $user->setLastseen(new \DateTime());
        $user->setLastip($this->remoteIP);
        $user->setFailedlogins(0);
        $user->setThrottleduntil($this->throttleUntil(0));

        $repository = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users');

        // Attempt to update the last login, but don't break on failure.
        try {
            $repository->save($user);
        } catch (DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
        }

        $user['sessionkey'] = $this->getAuthToken($user->getUsername());
        $this->app['session']->migrate(true);

        $this->app['session']->set('user', $user);
        $this->app['logger.flash']->success(Trans::__("You've been logged on successfully."));
    }

    /**
     * Remove expired sessions from the database.
     *
     * @return void
     */
    private function deleteExpiredSessions()
    {
        $this->repository->deleteExpiredTokens();
    }

    /**
     * Add errormessages to logs and update the user
     *
     * @param array $user
     *
     * @return false
     */
    private function loginFailed($user)
    {
        $this->app['logger.flash']->error(Trans::__('Username or password not correct. Please check your input.'));
        $this->app['logger.system']->info("Failed login attempt for '" . $user['displayname'] . "'.", ['event' => 'authentication']);

        // Update the failed login attempts, and perhaps throttle the logins.
        $update = [
            'failedlogins'   => $user['failedlogins'] + 1,
            'throttleduntil' => $this->throttleUntil($user['failedlogins'] + 1)
        ];

        // Attempt to update the last login, but don't break on failure.
        try {
            $this->app['db']->update($this->getTableName('users'), $update, ['id' => $user['id']]);
        } catch (DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
        }

        return false;
    }

    /**
     * Get a key to identify the session with.
     *
     * @param string $name
     * @param string $salt
     *
     * @return string|boolean
     */
    private function getAuthToken($name = '', $salt = '')
    {
        if (empty($name)) {
            return false;
        }

        $seed = $name . '-' . $salt;

        if ($this->app['config']->get('general/cookies_use_remoteaddr')) {
            $seed .= '-' . $this->remoteIP;
        }
        if ($this->app['config']->get('general/cookies_use_browseragent')) {
            $seed .= '-' . $this->userAgent;
        }
        if ($this->app['config']->get('general/cookies_use_httphost')) {
            $seed .= '-' . $this->hostName;
        }

        $token = md5($seed);

        return $token;
    }

    /**
     * Set the Authtoken cookie and DB-entry. If it's already present, update it.
     *
     * @return string
     */
    private function setAuthToken()
    {
        $salt = $this->app['randomgenerator']->generateString(12);
        $token = [
            'username'  => $this->app['users']->getCurrentUserProperty('username'),
            'token'     => $this->getAuthToken($this->app['users']->getCurrentUserProperty('username'), $salt),
            'salt'      => $salt,
            'validity'  => date('Y-m-d H:i:s', time() + $this->app['config']->get('general/cookies_lifetime')),
            'ip'        => $this->remoteIP,
            'lastseen'  => date('Y-m-d H:i:s'),
            'useragent' => $this->userAgent
        ];

        try {
            // Check if there's already a token stored for this name / IP combo.
            $query = sprintf('SELECT id FROM %s WHERE username=? AND ip=? AND useragent=?', $this->getTableName('authtoken'));
            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
            $row = $this->app['db']->executeQuery($query, [$token['username'], $token['ip'], $token['useragent']], [\PDO::PARAM_STR])->fetch();

            // Update or insert the row.
            if (empty($row)) {
                $this->app['db']->insert($this->getTableName('authtoken'), $token);
            } else {
                $this->app['db']->update($this->getTableName('authtoken'), $token, ['id' => $row['id']]);
            }
        } catch (DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
        }

        return $token['token'];
    }

    /**
     * Calculate the amount of time until we should throttle login attempts for
     * a user.
     *
     * The amount is increased exponentially with each attempt: 1, 4, 9, 16, 25,
     * 36, .. seconds.
     *
     * Note: I just realized this is conceptually wrong: we should throttle
     * based on remote_addr, not username. So, this isn't used, yet.
     *
     * @param integer $attempts
     *
     * @return \DateTime
     */
    private function throttleUntil($attempts)
    {
        if ($attempts < 5) {
            return null;
        } else {
            $wait = pow(($attempts - 4), 2);

            $dt = new \DateTime();
            $di = new \DateInterval("PT{$wait}S");

            return $dt->add($di);
        }
    }

    /**
     * Get the name of either the users or authtoken table.
     *
     * @param string $table
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function getTableName($table)
    {
        $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');

        if ($table === 'users') {
            return $prefix . 'users';
        } elseif ($table === 'authtoken') {
            return $prefix . 'authtoken';
        } else {
            throw new \InvalidArgumentException('Invalid table request.');
        }
    }
}
