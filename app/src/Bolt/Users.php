<?php

namespace Bolt;

use Silex;

/**
 * Class to handle things dealing with users..
 */
class Users
{
    const ANONYMOUS = 0;
    const EDITOR = 2;
    const ADMIN = 4;
    const DEVELOPER = 6;

    public $db;
    public $config;
    public $usertable;
    public $authtokentable;
    public $users;
    public $session;
    public $currentuser;
    public $allowed;
    private $hash_strength;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->db = $app['db'];

        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");

        // Hashstrength has a default of '10', don't allow less than '8'.
        $this->hash_strength = max($this->app['config']->get('general/hash_strength'), 8);

        $this->usertable = $prefix . "users";
        $this->authtokentable = $prefix . "authtoken";
        $this->users = array();
        $this->session = $app['session'];
        $this->remoteIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

        // Set 'validsession', to see if the current session is valid.
        $this->validsession = $this->checkValidSession();

        $this->allowed = array(
            'dashboard' => self::EDITOR,
            'settings' => self::ADMIN,
            'login' => self::ANONYMOUS,
            'logout' => self::EDITOR,
            'dbcheck' => self::ADMIN,
            'dbupdate' => self::ADMIN,
            'clearcache' => self::ADMIN,
            'prefill' => self::DEVELOPER,
            'users' => self::ADMIN,
            'useredit' => self::ADMIN,
            'useraction' => self::ADMIN,
            'overview' => self::EDITOR,
            'editcontent' => self::EDITOR,
            'editcontent:own' => self::EDITOR,
            'editcontent:all' => self::ADMIN,
            'contentaction' => self::EDITOR,
            'about' => self::EDITOR,
            'extensions' => self::DEVELOPER,
            'files' => self::EDITOR,
            'files:config' => self::DEVELOPER,
            'files:theme' => self::DEVELOPER,
            'files:uploads' => self::ADMIN,
            'translation' => self::DEVELOPER,
            'activitylog' => self::ADMIN,
            'fileedit' => self::ADMIN
        );
    }

    /**
     * Save changes to a user to the database. (re)hashing the password, if needed.
     *
     * @param  array $user
     * @return mixed
     */
    public function saveUser($user)
    {
        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array(
                'id',
                'username',
                'password',
                'email',
                'lastseen',
                'lastip',
                'displayname',
                'enabled',
                'stack',
                'roles',
            );

        // unset columns we don't need to store..
        foreach ($user as $key => $value) {
            if (!in_array($key, $allowedcolumns)) {
                unset($user[$key]);
            }
        }

        if (!empty($user['password']) && $user['password'] != "**dontchange**") {
            $hasher = new \Hautelook\Phpass\PasswordHash($this->hash_strength, true);
            $user['password'] = $hasher->HashPassword($user['password']);
        } else {
            unset($user['password']);
        }

        // make sure the username is slug-like
        $user['username'] = makeSlug($user['username']);

        if (empty($user['lastseen'])) {
            $user['lastseen'] = "1900-01-01";
        }

        if (empty($user['enabled']) && $user['enabled'] !== 0) {
            $user['enabled'] = 1;
        }

        if (empty($user['shadowvalidity'])) {
            $user['shadowvalidity'] = "1900-01-01";
        }

        if (empty($user['throttleduntil'])) {
            $user['throttleduntil'] = "1900-01-01";
        }

        if (empty($user['failedlogins'])) {
            $user['failedlogins'] = 0;
        }

        // Make sure the 'stack' is set.
        if (empty($user['stack'])) {
            $user['stack'] = json_encode(array());
        } elseif (is_array($user['stack'])) {
            $user['stack'] = json_encode($user['stack']);
        }

        // Serialize roles array
        if (empty($user['roles']) || !is_array($user['roles'])) {
            $user['roles'] = '[]';
        } else {
            $user['roles'] = json_encode(array_values(array_unique($user['roles'])));
        }

        // Decide whether to insert a new record, or update an existing one.
        if (empty($user['id'])) {
            unset($user['id']);

            return $this->db->insert($this->usertable, $user);
        } else {
            return $this->db->update($this->usertable, $user, array('id' => $user['id']));
        }
    }

    /**
     * Return whether or not the current session is valid.
     *
     * @return bool
     */
    public function isValidSession()
    {
        return $this->validsession;
    }

    /**
     * We will not allow tampering with sessions, so we make sure the current session
     * is still valid for the device on which it was created, and that the username,
     * ip-address are still the same.
     *
     * @return bool
     */
    public function checkValidSession()
    {
        if ($this->app['session']->get('user')) {
            $this->currentuser = $this->app['session']->get('user');
            if ($database = $this->getUser($this->currentuser['id'])) {
                // Update the session with the user from the database.
                $this->currentuser = array_merge($this->currentuser, $database);
            } else {
                // User doesn't exist anymore
                $this->logout();

                return false;
            }
            if (!$this->currentuser['enabled']) {
                // user has been disabled since logging in
                $this->logout();

                return false;
            }
        } else {
            // no current user, check if we can resume from authtoken cookie, or return without doing the rest.
            $result = $this->loginAuthtoken();

            return $result;
        }

        $key = $this->getAuthToken($this->currentuser['username']);

        if ($key != $this->currentuser['sessionkey']) {
            $this->app['log']->add("keys don't match. Invalidating session: $key != " . $this->currentuser['sessionkey'], 2);
            $this->app['log']->add("Automatically logged out user '".$this->currentuser['username']."': Session data didn't match.", 3, '', 'issue');
            $this->logout();

            return false;
        }

        // Check if user is _still_ allowed to log on..
        if (!$this->isAllowed('login') || !$this->currentuser['enabled']) {
            $this->logout();

            return false;
        }

        // Check if there's a bolt_authtoken cookie. If not, set it.
        if (empty($_COOKIE['bolt_authtoken'])) {
            $this->setAuthtoken();
        }

        return true;
    }

    /**
     * Get a key to identify the session with.
     *
     * @param  string $name
     * @param  string $salt
     * @return string
     */
    private function getAuthToken($name = "", $salt = "")
    {
        if (empty($name)) {
            return false;
        }

        $seed = $name . "-" . $salt;

        if ($this->app['config']->get('general/cookies_use_remoteaddr')) {
            $seed .= "-". $this->remoteIP;
        }
        if ($this->app['config']->get('general/cookies_use_browseragent')) {
            $seed .= "-". $_SERVER['HTTP_USER_AGENT'];
        }
        if ($this->app['config']->get('general/cookies_use_httphost')) {
            $seed .= "-". (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
        }

        $token = md5($seed);

        return $token;
    }

    /**
     * Set the Authtoken cookie and DB-entry. If it's already present, update it.
     */
    private function setAuthToken()
    {
        $salt = $this->app['randomgenerator']->generateString(12);
        $token = array(
            'username' => $this->currentuser['username'],
            'token' => $this->getAuthToken($this->currentuser['username'], $salt),
            'salt' => $salt,
            'validity' => date('Y-m-d H:i:s', time() + $this->app['config']->get('general/cookies_lifetime')),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'lastseen' => date('Y-m-d H:i:s'),
            'useragent' => $_SERVER['HTTP_USER_AGENT']
        );

        // Update or set the authtoken cookie..
        setcookie(
            'bolt_authtoken',
            $token['token'],
            time() + $this->app['config']->get('general/cookies_lifetime'),
            '/',
            $this->app['config']->get('general/cookies_domain'),
            $this->app['config']->get('general/cookies_https_only'),
            true
        );

        try {
            // Check if there's already a token stored for this name / IP combo.
            $query = "SELECT id FROM " . $this->authtokentable . " WHERE username=? AND ip=? AND useragent=?";
            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
            $row = $this->db->executeQuery($query, array($token['username'], $token['ip'], $token['useragent']), array(\PDO::PARAM_STR))->fetch();

            // Update or insert the row..
            if (empty($row)) {
                $this->db->insert($this->authtokentable, $token);
            } else {
                $this->db->update($this->authtokentable, $token, array('id' => $row['id']));
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
        }
    }

    /**
     * Generate a Anti-CSRF-like token, to use in GET requests for stuff that ought to be POST-ed forms.
     *
     * @return string $token
     */
    public function getAntiCSRFToken()
    {
        $seed = $this->app['request']->cookies->get('bolt_session');

        if ($this->app['config']->get('general/cookies_use_remoteaddr')) {
            $seed .= "-". $this->remoteIP;
        }
        if ($this->app['config']->get('general/cookies_use_browseragent')) {
            $seed .= "-". $_SERVER['HTTP_USER_AGENT'];
        }
        if ($this->app['config']->get('general/cookies_use_httphost')) {
            $seed .= "-". (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
        }

        $token = substr(md5($seed), 0, 8);

        return $token;
    }

    /**
     * Check if a given token matches the current (correct) Anit-CSRF-like token
     *
     * @param  string $token
     * @return bool
     */
    public function checkAntiCSRFToken($token = '')
    {
        if (empty($token)) {
            $token = $this->app['request']->get('token');
        }

        if ($token === $this->getAntiCSRFToken()) {
            return true;
        } else {
            $this->app['session']->getFlashBag()->set('error', "The security token was incorrect. Please try again.");

            return false;
        }
    }

    public function getActiveSessions()
    {
        $this->deleteExpiredSessions();

        $query = "SELECT * FROM " . $this->authtokentable;
        $sessions = $this->db->fetchAll($query);

        return $sessions;
    }

    private function deleteExpiredSessions()
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM " . $this->authtokentable . " WHERE validity < :now");
            $stmt->bindValue("now", date("Y-m-d H:i:s"));
            $stmt->execute();
        } catch (\Doctrine\DBAL\DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
        }
    }

    /**
     * Remove a user from the database.
     *
     * @param  int  $id
     * @return bool
     */
    public function deleteUser($id)
    {
        $user = $this->getUser($id);

        if (empty($user['id'])) {
            $this->session->getFlashBag()->set('error', __('That user does not exist.'));

            return false;
        } else {
            return $this->db->delete($this->usertable, array('id' => $user['id']));
        }
    }

    /**
     * Attempt to login a user with the given password
     *
     * @param  string $user
     * @param  string $password
     * @return bool
     */
    public function login($user, $password)
    {
        $userslug = makeSlug($user);

        // for once we don't use getUser(), because we need the password.
        $query = "SELECT * FROM " . $this->usertable . " WHERE username=?";
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
        $user = $this->db->executeQuery($query, array($userslug), array(\PDO::PARAM_STR))->fetch();

        if (empty($user)) {
            $this->session->getFlashBag()->set('error', __('Username or password not correct. Please check your input.'));

            return false;
        }

        $hasher = new \Hautelook\Phpass\PasswordHash($this->hash_strength, true);

        if ($hasher->CheckPassword($password, $user['password'])) {

            if (!$user['enabled']) {
                $this->session->getFlashBag()->set('error', __('Your account is disabled. Sorry about that.'));

                return false;
            }

            $update = array(
                'lastseen' => date('Y-m-d H:i:s'),
                'lastip' => $this->remoteIP,
                'failedlogins' => 0,
                'throttleduntil' => $this->throttleUntil(0)
            );

            // Attempt to update the last login, but don't break on failure.
            try {
                $this->db->update($this->usertable, $update, array('id' => $user['id']));
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            }

            $user = $this->getUser($user['id']);

            $user['sessionkey'] = $this->getAuthToken($user['username']);

            // We wish to create a new session-id for extended security, but due to a bug in PHP < 5.4.11, this
            // will throw warnings. Suppress them here. #shakemyhead
            // @see: https://bugs.php.net/bug.php?id=63379
            @$this->session->migrate(true);
            $this->session->set('user', $user);
            $this->session->getFlashBag()->set('success', __("You've been logged on successfully."));

            $this->currentuser = $user;

            $this->setAuthToken();

            return true;

        } else {

            $this->session->getFlashBag()->set('error', __('Username or password not correct. Please check your input.'));
            $this->app['log']->add("Failed login attempt for '" . $user['displayname'] . "'.", 3, '', 'issue');

            // Update the failed login attempts, and perhaps throttle the logins.
            $update = array(
                'failedlogins' => $user['failedlogins'] + 1,
                'throttleduntil' => $this->throttleUntil($user['failedlogins'] + 1)
            );

            // Attempt to update the last login, but don't break on failure.
            try {
                $this->db->update($this->usertable, $update, array('id' => $user['id']));
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            }

            // Take a nap, to prevent brute-forcing. Zzzzz...
            sleep(1);

            return false;
        }
    }

    /**
     * Attempt to login a user via the bolt_authtoken cookie
     *
     * @return bool
     */
    public function loginAuthtoken()
    {
        // If there's no cookie, we can't resume a session from the authtoken.
        if (empty($_COOKIE['bolt_authtoken'])) {
            return false;
        }

        $authtoken = $_COOKIE['bolt_authtoken'];
        $remoteip = $this->remoteIP;
        $browser = $_SERVER['HTTP_USER_AGENT'];

        $this->deleteExpiredSessions();

        // Check if there's already a token stored for this token / IP combo.
        try {
            $query = "SELECT * FROM " . $this->authtokentable . " WHERE token=? AND ip=? AND useragent=?";
            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
            $row = $this->db->executeQuery($query, array($authtoken, $remoteip, $browser), array(\PDO::PARAM_STR))->fetch();
        } catch (\Doctrine\DBAL\DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
        }

        // If there's no row, we can't resume a session from the authtoken.
        if (empty($row)) {
            return false;
        }

        $checksalt = $this->getAuthToken($row['username'], $row['salt']);

        if ($checksalt == $row['token']) {

            $user = $this->getUser($row['username']);

            $update = array(
                'lastseen' => date('Y-m-d H:i:s'),
                'lastip' => $this->remoteIP,
                'failedlogins' => 0,
                'throttleduntil' => $this->throttleUntil(0)
            );

            // Attempt to update the last login, but don't break on failure.
            try {
                $this->db->update($this->usertable, $update, array('id' => $user['id']));
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            }

            $user['sessionkey'] = $this->getAuthToken($user['username']);

            $this->session->set('user', $user);
            $this->session->getFlashBag()->set('success', __("Session resumed."));

            $this->currentuser = $user;

            $this->setAuthToken();

            return true;

        } else {
            // Delete the authtoken cookie..
            setcookie(
                'bolt_authtoken',
                '',
                time() - 1,
                '/',
                $this->app['config']->get('general/cookies_domain'),
                $this->app['config']->get('general/cookies_https_only'),
                true
            );

            return false;

        }
    }

    public function resetPasswordRequest($username)
    {
        $user = $this->getUser($username);

        // For safety, this is the message we display, regardless of whether $user exists.
        $this->session->getFlashBag()->set('info', __("A password reset link has been sent to '%user%'.", array('%user%' => $username)));

        if (!empty($user)) {

            $shadowpassword = $this->app['randomgenerator']->generateString(12);
            $shadowtoken = $this->app['randomgenerator']->generateString(32);

            $hasher = new \Hautelook\Phpass\PasswordHash($this->hash_strength, true);
            $shadowhashed = $hasher->HashPassword($shadowpassword);

            $shadowlink = sprintf(
                "%s%sresetpassword?token=%s",
                $this->app['paths']['hosturl'],
                $this->app['paths']['bolt'],
                $shadowtoken
            );

            // Set the shadow password and related stuff in the database..
            $update = array(
                'shadowpassword' => $shadowhashed,
                'shadowtoken' => $shadowtoken . "-" . str_replace(".", "-", $this->remoteIP),
                'shadowvalidity' => date("Y-m-d H:i:s", strtotime("+2 hours"))
            );
            $this->db->update($this->usertable, $update, array('id' => $user['id']));

            // Compile the email with the shadow password and reset link..
            $mailhtml = $this->app['render']->render(
                'mail/passwordreset.twig',
                array(
                    'user' => $user,
                    'shadowpassword' => $shadowpassword,
                    'shadowtoken' => $shadowtoken,
                    'shadowvalidity' => date("Y-m-d H:i:s", strtotime("+2 hours")),
                    'shadowlink' => $shadowlink
                )
            );

            // echo $mailhtml;

            $subject = sprintf("[ Bolt / %s ] Password reset.", $this->app['config']->get('general/sitename'));

            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(array($user['email'] => "Bolt"))
                ->setTo(array($user['email'] => $user['displayname']))
                ->setBody(strip_tags($mailhtml))
                ->addPart($mailhtml, 'text/html');

            $res = $this->app['mailer']->send($message);

            if ($res) {
                $this->app['log']->add("Password request sent to '" . $user['displayname'] . "'.", 3, '', 'issue');
            } else {
                $this->app['log']->add("Failed to send password request sent to '" . $user['displayname'] . "'.", 3, '', 'issue');
            }

        }

        // Take a nap, to prevent brute-forcing. Zzzzz...
        sleep(1);

        return true;
    }

    public function resetPasswordConfirm($token)
    {
        $token .= "-" . str_replace(".", "-", $this->remoteIP);

        $now = date("Y-m-d H:i:s");

        // Let's see if the token is valid, and it's been requested within two hours...
        $query = "SELECT * FROM " . $this->usertable . " WHERE shadowtoken = ? AND shadowvalidity > ?";
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
        $user = $this->db->executeQuery($query, array($token, $now), array(\PDO::PARAM_STR))->fetch();

        if (!empty($user)) {

            // allright, we can reset this user..
            $this->app['session']->getFlashBag()->set('success', __("Password reset successful! You can now log on with the password that was sent to you via email."));

            $update = array(
                'password' => $user['shadowpassword'],
                'shadowpassword' => "",
                'shadowtoken' => "",
                'shadowvalidity' => "0000-00-00 00:00:00"
            );
            $this->db->update($this->usertable, $update, array('id' => $user['id']));

        } else {

            // That was not a valid token, or too late, or not from the correct IP.
            $this->app['log']->add("Somebody tried to reset a password with an invalid token.", 3, '', 'issue');
            $this->app['session']->getFlashBag()->set('error', __("Password reset not successful! Either the token was incorrect, or you were too late, or you tried to reset the password from a different IP-address."));

        }
    }

    /**
     * Calculate the amount of time until we should throttle login attempts for a user.
     * The amount is increased exponentially with each attempt: 1, 4, 9, 16, 25, 36, .. seconds.
     *
     * Note: I just realized this is conceptually wrong: we should throttle based on
     * remote_addr, not username. So, this isn't used, yet.
     *
     * @param $attempts
     * @return string
     */
    private function throttleUntil($attempts)
    {
        if ($attempts < 5) {
            return "0000-00-00 00:00:00";
        } else {
            $wait = pow(($attempts - 4), 2);

            return date("Y-m-d H:i:s", strtotime("+$wait seconds"));
        }
    }

    /**
     * Log out the currently logged in user.
     *
     */
    public function logout()
    {
        $this->session->getFlashBag()->set('info', __('You have been logged out.'));
        $this->session->remove('user');
        @$this->session->migrate(true);

        // Remove all auth tokens when logging off a user (so we sign out _all_ this user's sessions on all locations)
        try {
            $this->db->delete($this->authtokentable, array('username' => $this->currentuser['username']));
        } catch (\Exception $e) {
            // Nope. No auth tokens to be deleted. .
        }

        // Remove the cookie..
        setcookie(
            'bolt_authtoken',
            '',
            time() - 1,
            '/',
            $this->app['config']->get('general/cookies_domain'),
            $this->app['config']->get('general/cookies_https_only'),
            true
        );
    }

    /**
     * Create a stub for a new/empty user.
     *
     * @return array
     */
    public function getEmptyUser()
    {
        $user = array(
            'id' => '',
            'username' => '',
            'password' => '',
            'email' => '',
            'lastseen' => '',
            'lastip' => '',
            'displayname' => '',
            'enabled' => '1',
            'shadowpassword' => '',
            'shadowtoken' => '',
            'shadowvalidity' => '',
            'failedlogins' => 0,
            'throttleduntil' => ''
        );

        return $user;
    }

    /**
     * Get an array with the current users.
     *
     * @return array
     */
    public function getUsers()
    {
        if (empty($this->users) || !is_array($this->users)) {

            $query = "SELECT * FROM " . $this->usertable;
            $this->users = array();

            try {
                $tempusers = $this->db->fetchAll($query);

                foreach ($tempusers as $user) {
                    $key = $user['username'];
                    $this->users[$key] = $user;
                    $this->users[$key]['password'] = "**dontchange**";

                    $roles = json_decode($this->users[$key]['roles']);
                    if (!is_array($roles)) {
                        $roles = array();
                    }
                    // add "everyone" role to, uhm, well, everyone.
                    $roles[] = Permissions::ROLE_EVERYONE;
                    $this->users[$key]['roles'] = array_unique($roles);
                }
            } catch (\Exception $e) {
                // Nope. No users.
            }
        }

        return $this->users;
    }

    /**
     * Get a user, specified by id. Return 'false' if no user found.
     *
     * @param  int   $id
     * @return array
     */
    public function getUser($id)
    {
        // Make sure we've fetched the users..
        $this->getUsers();

        if (is_numeric($id)) {
            foreach ($this->users as $user) {
                if ($user['id'] == $id) {
                    return $user;
                }
            }
        } else {
            if (isset($this->users[$id])) {
                return $this->users[$id];
            }
        }

        // otherwise..
        return false;
    }

    /**
     * Get the current user as an array
     *
     * @return array
     */
    public function getCurrentUser()
    {
        return $this->currentuser;
    }

    /**
     * Get the username of the current user.
     *
     * @return string the username of the current user.
     */
    public function getCurrentUsername()
    {
        return $this->currentuser['username'];
    }

    /**
     * Enable or disable a user, specified by id.
     *
     * @param  int  $id
     * @param  int  $enabled
     * @return bool
     */
    public function setEnabled($id, $enabled = 1)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            return false;
        }

        $user['enabled'] = $enabled;

        return $this->saveUser($user);
    }

    /**
     * Check if a certain user has a specific role
     *
     * @param  mixed  $id
     * @param  string $role
     * @return bool
     */
    public function hasRole($id, $role)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            return false;
        }

        return (is_array($user['roles']) && in_array($role, $user['roles']));
    }

    /**
     * Add a certain role from a specific user.
     *
     * @param  mixed  $id
     * @param  string $role
     * @return bool
     */
    public function addRole($id, $role)
    {
        $user = $this->getUser($id);

        if (empty($user) || empty($role)) {
            return false;
        }

        // Add the role to the $user['roles'] array
        $user['roles'][] = (string) $role;

        return $this->saveUser($user);
    }

    /**
     * Remove a certain role from a specific user.
     *
     * @param  mixed  $id
     * @param  string $role
     * @return bool
     */
    public function removeRole($id, $role)
    {
        $user = $this->getUser($id);

        if (empty($user) || empty($role)) {
            return false;
        }

        // Remove the role from the $user['roles'] array.
        $user['roles'] = array_diff($user['roles'], array((string) $role));

        return $this->saveUser($user);
    }

    /**
     * Check for a user with the 'root' role. There should always be at least one
     * If there isn't we promote the current user.
     *
     * @return bool
     */
    public function checkForRoot()
    {
        // Don't check for root, if we're not logged in.
        if ($this->getCurrentUsername() == false) {
            return false;
        }

        // Loop over the users, check if anybody's root.
        foreach ($this->getUsers() as $user) {
            if (is_array($user['roles']) && in_array('root', $user['roles'])) {
                // We have a 'root' user.
                return true;
            }
        }

        // Make sure the DB is updated. Note, that at this point we currently don't have
        // the permissions to do so, but if we don't, update the DB, we can never add the
        // role 'root' to the current user.
        $this->app['integritychecker']->repairTables();

        // If we reach this point, there is no user 'root'. We promote the current user.
        $this->addRole($this->getCurrentUsername(), 'root');

        // Show a helpful message to the user.
        $this->app['session']->getFlashBag()->set('info', __("There should always be at least one 'root' user. You have just been promoted. Congratulations!"));
    }

    /**
     * Runs a permission check. Permissions are encoded as strings, where
     * the ':' character acts as a separator for dynamic parts and
     * sub-permissions.
     * Apart from the route-based rules defined in permissions.yml, the
     * following special cases are available:
     *
     * "overview:$contenttype" - view the overview for the content type. Alias
     *                           for "contenttype:$contenttype:view".
     * "contenttype:$contenttype",
     * "contenttype:$contenttype:view",
     * "contenttype:$contenttype:view:$id" - View any item or a particular item
     *                                       of the specified content type.
     * "contenttype:$contenttype:edit",
     * "contenttype:$contenttype:edit:$id" - Edit any item or a particular item
     *                                       of the specified content type.
     * "contenttype:$contenttype:create" - Create a new item of the specified
     *                                     content type. (It doesn't make sense
     *                                     to provide this permission on a
     *                                     per-item basis, for obvious reasons)
     * "contenttype:$contenttype:change-ownership",
     * "contenttype:$contenttype:change-ownership:$id" - Change the ownership
     *                                of the specified content type or item.
     *
     * @param  string $what The desired permission, as elaborated upon above.
     * @return bool   TRUE if the permission is granted, FALSE if denied.
     */
    public function isAllowed($what, $contenttype = null, $contentid = null)
    {
        $user = $this->currentuser;

        return $this->app['permissions']->isAllowed($what, $user, $contenttype, $contentid);
    }

    public function isContentStatusTransitionAllowed($fromStatus, $toStatus, $contenttype, $contentid = null)
    {
        $user = $this->currentuser;

        return $this->app['permissions']->isContentStatusTransitionAllowed($fromStatus, $toStatus, $user, $contenttype, $contentid);
    }

    private function canonicalizeFieldValue($fieldname, $fieldvalue)
    {
        switch ($fieldname) {
            case 'email':
                return strtolower(trim($fieldvalue));

            case 'username':
                return strtolower(preg_replace('/[^a-zA-Z0-9_\\-]/', '', $fieldvalue));

            default:
                return trim($fieldvalue);
        }
    }

    /**
     * Check if a certain field with a certain value doesn't exist already.
     * Depending on the field type, different pre-massaging of the compared
     * values are applied, because what constitutes 'equal' for the purpose
     * of this filtering depends on the field type.
     *
     * @param  string $fieldname
     * @param  string $value
     * @param  int    $currentid
     * @return bool
     */
    public function checkAvailability($fieldname, $value, $currentid = 0)
    {
        foreach ($this->users as $user) {
            if (($this->canonicalizeFieldValue($fieldname, $user[$fieldname]) ===
                 $this->canonicalizeFieldValue($fieldname, $value)) &&
                ($user['id'] != $currentid)
            ) {
                return false;
            }
        }

        // no clashes found, OK!
        return true;
    }
}
