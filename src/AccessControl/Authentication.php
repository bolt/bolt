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
    /** @var \Bolt\Storage\Repository\AuthtokenRepository $repositoryAuthtoken */
    private $repositoryAuthtoken;
    /** @var \Bolt\Storage\Repository\UsersRepository $repositoryUsers */
    private $repositoryUsers;

    /** @var string */
    private $remoteIP;
    /** @var string */
    private $userAgent;
    /** @var string */
    private $hostName;
    /** @var string */
    private $authToken;

    /**
     * Constructor.
     *
     * @param Application                    $app
     * @param Repository\AuthtokenRepository $repositoryAuthtoken
     * @param Repository\UsersRepository     $repositoryUsers
     */
    public function __construct(Application $app, Repository\AuthtokenRepository $repositoryAuthtoken, Repository\UsersRepository $repositoryUsers)
    {
        $this->app = $app;
        $this->repositoryAuthtoken = $repositoryAuthtoken;
        $this->repositoryUsers = $repositoryUsers;

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
     * We will not allow tampering with sessions, so we make sure the current
     * session is still valid for the device on which it was created, and that
     * the username, and IP address, are still the same.
     *
     * @return boolean
     */
    public function checkValidSession()
    {
        if (!($this->app['session']->isStarted() && $sessionUser = $this->app['session']->get('user'))) {
            // No current user, check if we can resume from authtoken cookie, or
            // return without doing the rest.
            return $this->loginAuthtoken();
        } elseif (!$databaseUser = $this->repositoryUsers->getUser($sessionUser->getId())) {
            // User doesn't exist anymore
            return $this->logout();
        } elseif (!$databaseUser->getEnabled()) {
            // User has been disabled since logging in
            return $this->logout();
        }

        // The auth token is based on hostname, IP and browser user agent
        $key = $this->getAuthToken($sessionUser->getUsername());

        if ($key !== $sessionUser->getSessionkey()) {
            $this->app['logger.system']->error("Keys don't match. Invalidating session: $key != " . $sessionUser->getSessionkey(), ['event' => 'authentication']);
            $this->app['logger.system']->info("Automatically logged out user '" . $sessionUser->getUsername() . "': Session data didn't match.", ['event' => 'authentication']);

            return $this->logout();
        }

        // Check if user is _still_ allowed to log on.
        if (!$this->app['permissions']->isAllowed('login') || !$sessionUser->getEnabled()) {
            return $this->logout();
        }

        // Check if there's a bolt_authtoken cookie. If not, set it.
        if (empty($this->authToken)) {
            $this->setAuthtoken($databaseUser);
        }

        $this->setCurrentUser($databaseUser);

        return true;
    }

    /**
     * Lookup active sessions.
     *
     * @return array
     */
    public function getActiveSessions()
    {
        // Parse the user-agents to get a user-friendly Browser, version and platform.
        $parser = UAParser\Parser::create();
        $this->repositoryAuthtoken->deleteExpiredTokens();
        $sessions = $this->repositoryAuthtoken->getActiveSessions() ?: [];

        foreach ($sessions as &$session) {
            $ua = $parser->parse($session->getUseragent());
            $session->setBrowser(sprintf('%s / %s', $ua->ua->toString(), $ua->os->toString()));
        }

        return $sessions;
    }

    /**
     * Generate a Anti-CSRF-like token, to use in GET requests for stuff that
     * ought to be POST-ed forms.
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
     * @return boolean
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
     * Attempt to login a user with the given password. Accepts username or
     * email.
     *
     * @param string $user
     * @param string $password
     *
     * @return boolean|string
     */
    public function login($user, $password)
    {
        if (!$userEntity = $this->repositoryUsers->getUser($user)) {
            $this->app['logger.flash']->error(Trans::__('Your account is disabled. Sorry about that.'));

            return false;
        }

        if (!$userEntity->getEnabled()) {
            $this->app['logger.flash']->error(Trans::__('Your account is disabled. Sorry about that.'));

            return false;
        }

        $hasher = new PasswordHash($this->hashStrength, true);
        if (!$hasher->CheckPassword($password, $userEntity->getPassword())) {
            return $this->loginFailed($userEntity);
        }

        $this->setCurrentUser($userEntity);
        $this->updateUserLogin($userEntity);

        return $this->setAuthToken($userEntity);
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

        $token = $this->authToken;
        $ip = $this->remoteIP;
        $useragent = $this->userAgent;

        $this->repositoryAuthtoken->deleteExpiredTokens();

        // Check if there's already a token stored for this token / IP combo.
        // If there's no row, we can't resume a session from the authtoken.
        if (!$userTokenEntity = $this->repositoryAuthtoken->getToken($token, $ip, $useragent)) {
            return false;
        }

        $checksalt = $this->getAuthToken($userTokenEntity->getUsername(), $userTokenEntity->getSalt());
        if ($checksalt === $userTokenEntity->getToken()) {
            // Update the login details in the user record
            $userEntity = $this->repositoryUsers->getUser($userTokenEntity->getUsername());
            $userEntity->setLastseen(new \DateTime());
            $userEntity->setLastip($this->remoteIP);
            $userEntity->setFailedlogins(0);
            $userEntity->setThrottleduntil($this->throttleUntil(0));

            $this->repositoryAuthtoken->save($userEntity);

            $userEntity->setSessionkey($this->getAuthToken($userEntity->getUsername()));
            $this->app['session']->set('user', $userEntity);
            $this->app['logger.flash']->success(Trans::__('Session resumed.'));

            $this->setCurrentUser($userEntity);

            return $this->setAuthToken($userEntity);
        } else {
            // Implementation note:
            // This needs to be caught in the controller and the authtoken
            // cookie deleted: $response->headers->clearCookie($this->app['token.authentication.name']);
            return false;
        }
    }

    /**
     * Log out the currently logged in user.
     *
     * @return boolean
     */
    public function logout()
    {
        $this->app['logger.flash']->info(Trans::__('You have been logged out.'));

        // Remove all auth tokens when logging off a user (so we sign out _all_ this user's sessions on all locations)
        if ($userEntity = $this->app['session']->get('user')) {
            $this->repositoryAuthtoken->deleteTokens($userEntity->getUsername());
        }

        $this->app['session']->remove('user');
        $this->app['session']->migrate(true);

        return false;
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

        if ($userEntity = $this->repositoryUsers->getUser($username)) {
            $password = $this->app['randomgenerator']->generateString(12);

            $hasher = new PasswordHash($this->hashStrength, true);
            $hashedpassword = $hasher->HashPassword($password);

            $userEntity->setPassword($hashedpassword);
            $userEntity->setShadowpassword('');
            $userEntity->setShadowtoken('');
            $userEntity->setShadowvalidity(null);

            $this->repositoryUsers->save($userEntity);

            $this->app['logger.system']->info(
                "Password for user '{$userEntity->getUsername()}' was reset via Nut.",
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
        // Append the remote caller's IP to the token
        $token .= '-' . str_replace('.', '-', $this->remoteIP);

        if ($userEntity = $this->repositoryUsers->getUserShadowAuth($token)) {
            // Update entries
            $userEntity->setPassword($userEntity->getShadowpassword());
            $userEntity->setShadowpassword('');
            $userEntity->setShadowtoken('');
            $userEntity->setShadowvalidity(null);
            $this->repositoryUsers->save($userEntity);

            $this->app['logger.flash']->success(Trans::__('Password reset successful! You can now log on with the password that was sent to you via email.'));
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
        $userEntity = $this->repositoryUsers->getUser($username);

        if (!$userEntity) {
            // For safety, this is the message we display, regardless of whether user exists.
            $this->app['logger.flash']->info(Trans::__("A password reset link has been sent to '%user%'.", ['%user%' => $username]));

            return false;
        }

        $shadowpassword = $this->app['randomgenerator']->generateString(12);
        $shadowtoken = $this->app['randomgenerator']->generateString(32);
        $hasher = new PasswordHash($this->hashStrength, true);
        $shadowhashed = $hasher->HashPassword($shadowpassword);
        $validity = new \DateTime();
        $delay = new \DateInterval(PT2H);

        // Set the shadow password and related stuff in the database.
        $userEntity->setShadowpassword($shadowhashed);
        $userEntity->setShadowtoken($shadowtoken . '-' . str_replace('.', '-', $this->remoteIP));
        $userEntity->setShadowvalidity($validity->add($delay));
        $this->repositoryUsers->save($userEntity);

        // Sent the password reset notification
        $this->resetPasswordNotification($userEntity, $shadowpassword, $shadowtoken);

        return true;
    }

    /**
     * Send the password reset link notification to the user.
     *
     * @param Entity\Users $userEntity
     * @param string       $shadowpassword
     * @param string       $shadowtoken
     */
    private function resetPasswordNotification(Entity\Users $userEntity, $shadowpassword, $shadowtoken)
    {
        $shadowlink = sprintf(
            '%s%sresetpassword?token=%s',
            $this->app['resources']->getUrl('hosturl'),
            $this->app['resources']->getUrl('bolt'),
            urlencode($shadowtoken)
        );

        // Compile the email with the shadow password and reset link.
        $mailhtml = $this->app['render']->render(
            'mail/passwordreset.twig',
            [
                'user'           => $userEntity,
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
            ->setFrom([$this->app['config']->get('general/mailoptions/senderMail', $userEntity->getEmail()) => $this->app['config']->get('general/mailoptions/senderName', $this->app['config']->get('general/sitename'))])
            ->setTo([$userEntity['email'] => $userEntity['displayname']])
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html');

        $recipients = $this->app['mailer']->send($message);

        if ($recipients) {
            $this->app['logger.system']->info("Password request sent to '" . $userEntity->getDisplayname() . "'.", ['event' => 'authentication']);
        } else {
            $this->app['logger.system']->error("Failed to send password request sent to '" . $userEntity['displayname'] . "'.", ['event' => 'authentication']);
            $this->app['logger.flash']->error(Trans::__("Failed to send password request. Please check the email settings."));
        }
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

        $this->repositoryUsers->save($user);

        $user['sessionkey'] = $this->getAuthToken($user->getUsername());
        $this->app['session']->migrate(true);

        $this->app['session']->set('user', $user);
        $this->app['logger.flash']->success(Trans::__("You've been logged on successfully."));
    }

    /**
     * Add errormessages to logs and update the user
     *
     * @param Entity\Users $userEntity
     *
     * @return false
     */
    private function loginFailed(Entity\Users $userEntity)
    {
        $this->app['logger.flash']->error(Trans::__('Username or password not correct. Please check your input.'));
        $this->app['logger.system']->info("Failed login attempt for '" . $userEntity->getDisplayname() . "'.", ['event' => 'authentication']);

        // Update the failed login attempts, and perhaps throttle the logins.
        $userEntity->setFailedlogins($userEntity->getFailedlogins() + 1);
        $userEntity->setThrottleduntil($this->throttleUntil($userEntity->getFailedlogins() + 1));
        $this->repositoryUsers->save($userEntity);

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
     * @param Entity\User $userEntity
     *
     * @return string
     */
    private function setAuthToken($userEntity)
    {
        $salt = $this->app['randomgenerator']->generateString(12);

        if (!$tokenEntity = $this->repositoryAuthtoken->getUserToken($userEntity->getUsername(), $this->remoteIP, $this->userAgent)) {
            $tokenEntity = new Entity\Authtoken();
        }

        $validityPeriod = $this->app['config']->get('general/cookies_lifetime', 1209600);
        $validityDate = new \DateTime();
        $validityInterval = new \DateInterval("PT{$validityPeriod}S");

        $tokenEntity->setUsername($userEntity->getUsername());
        $tokenEntity->setToken($this->getAuthToken($userEntity->getUsername(), $salt));
        $tokenEntity->setSalt($salt);
        $tokenEntity->setValidity($validityDate->add($validityInterval));
        $tokenEntity->setIp($this->remoteIP);
        $tokenEntity->setLastseen(new \DateTime());
        $tokenEntity->setUseragent($this->userAgent);

        $this->repositoryAuthtoken->save($tokenEntity);

        return $tokenEntity->getToken();
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
     * Set the current user.
     *
     * @param Entity\Users $user
     */
    private function setCurrentUser(Entity\Users $user)
    {
        $user->setPassword('**dontchange**');
        $this->app['session']->set('user', $user);
    }
}
