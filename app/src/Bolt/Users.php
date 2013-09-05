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
            'files' => self::ADMIN,
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
        $allowedcolumns = array('id', 'username', 'password', 'email', 'lastseen', 'lastip', 'displayname', 'userlevel', 'enabled', 'contenttypes');

        // unset columns we don't need to store..
        foreach ($user as $key => $value) {
            if (!in_array($key, $allowedcolumns)) {
                unset($user[$key]);
            }
        }

        if (!empty($user['password']) && $user['password']!="**dontchange**") {
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

        if (empty($user['userlevel'])) {
            $user['userlevel'] = key(array_slice($this->getUserLevels(), -1));
        }

        if (empty($user['enabled']) && $user['enabled']!== 0) {
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

        // Serialize the contenttypes..
        if (empty($user['contenttypes'])) {
            $user['contenttypes'] = array();
        }
        $user['contenttypes'] = serialize($user['contenttypes']);

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
            }
        } else {
            // no current user, check if we can resume from authtoken cookie, or return without doing the rest.
            $result = $this->loginAuthtoken();
            return $result;
        }

        if (intval($this->currentuser['userlevel']) <= self::ANONYMOUS) {
            $this->logout();
            return false;
        }

        // set the rights for each of the contenttypes for this user.
        foreach ($this->app['config']->get('contenttypes') as $key => $contenttype) {
            if (in_array($key, $this->currentuser['contenttypes'])) {
                $this->allowed['contenttype:' . $key] = self::EDITOR;
            } else {
                $this->allowed['contenttype:' . $key] = self::ADMIN;
            }
        }

        $key = $this->getAuthtoken($this->currentuser['username']);

        if ($key != $this->currentuser['sessionkey']) {
            $this->app['log']->add("keys don't match. Invalidating session: $key != " . $this->currentuser['sessionkey'], 2);
            $this->app['log']->add("Automatically logged out user '".$this->currentuser['username']."': Session data didn't match.", 3, '', 'issue');
            $this->logout();
            return false;
        }

        // Check if user is _still_ allowed to log on..
        if (($this->currentuser['userlevel'] < self::EDITOR) || !$this->currentuser['enabled']) {
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
     * @return string
     */
    private function getAuthtoken($name = "", $salt = "")
    {

        if (empty($name)) {
            return false;
        }

        $key = $name . "-" . $salt;

        if ($this->app['config']->get('general/cookies_use_remoteaddr')) {
            $key .= "-". $_SERVER['REMOTE_ADDR'];
        }
        if ($this->app['config']->get('general/cookies_use_browseragent')) {
            $key .= "-". $_SERVER['HTTP_USER_AGENT'];
        }
        if ($this->app['config']->get('general/cookies_use_httphost')) {
            $key .= "-". (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME']);
        }

        $key = md5($key);

        return $key;

    }

    /**
     * Set the Authtoken cookie and DB-entry. If it's already present, update it.
     */
    private function setAuthtoken()
    {

        $salt = makekey(12);
        $token = array(
            'username' => $this->currentuser['username'],
            'token' => $this->getAuthtoken($this->currentuser['username'], $salt),
            'salt' => $salt,
            'validity' => date('Y-m-d H:i:s', time() + $this->app['config']->get('general/cookies_lifetime')),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'lastseen' => date('Y-m-d H:i:s'),
            'useragent' => getBrowserInfo()
        );

        // Update or set the authtoken cookie..
        setcookie(
            'bolt_authtoken',
            $token['token'],
            time() + $this->app['config']->get('general/cookies_lifetime'),
            '/',
            $this->app['config']->get('general/cookies_domain')
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
                'lastip' => $_SERVER['REMOTE_ADDR'],
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

            $user['sessionkey'] = $this->getAuthtoken($user['username']);

            $this->session->set('user', $user);
            $this->session->getFlashBag()->set('success', __("You've been logged on successfully."));

            $this->currentuser = $user;

            $this->setAuthtoken();

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
        $remoteip = $_SERVER['REMOTE_ADDR'];
        $browser = getBrowserInfo();

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

        $checksalt = $this->getAuthtoken($row['username'], $row['salt']);

        if ($checksalt == $row['token']) {

            $user = $this->getUser($row['username']);

            $update = array(
                'lastseen' => date('Y-m-d H:i:s'),
                'lastip' => $_SERVER['REMOTE_ADDR'],
                'failedlogins' => 0,
                'throttleduntil' => $this->throttleUntil(0)
            );

            // Attempt to update the last login, but don't break on failure.
            try {
                $this->db->update($this->usertable, $update, array('id' => $user['id']));
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            }

            $user['sessionkey'] = $this->getAuthtoken($user['username']);

            $this->session->set('user', $user);
            $this->session->getFlashBag()->set('success', __("Session resumed."));

            $this->currentuser = $user;

            $this->setAuthtoken();

            return true;

        } else {
            // Delete the authtoken cookie..
            setcookie('bolt_authtoken', '', time() -1 , '/', $this->app['config']->get('general/cookies_domain'));

            return false;

        }

    }


    public function resetPasswordRequest($username)
    {

        $user = $this->getUser($username);

        // For safety, this is the message we display, regardless of whether $user exists.
        $this->session->getFlashBag()->set('info', __("A password reset link has been sent to '%user%'.", array('%user%' => $username)));

        if (!empty($user)) {

            $shadowpassword = makeKey(10, true);
            $shadowtoken = makeKey(32, false);

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
                'shadowtoken' => $shadowtoken . "-" . str_replace(".", "-", $_SERVER['REMOTE_ADDR']),
                'shadowvalidity' => date("Y-m-d H:i:s", strtotime("+2 hours"))
            );
            $this->db->update($this->usertable, $update, array('id' => $user['id']));

            // Compile the email with the shadow password and reset link..
            $mailhtml = $this->app['twig']->render('mail/passwordreset.twig', array(
                'user' => $user,
                'shadowpassword' => $shadowpassword,
                'shadowtoken' => $shadowtoken,
                'shadowvalidity' => date("Y-m-d H:i:s", strtotime("+2 hours")),
                'shadowlink' => $shadowlink
            ));

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

        $token .= "-" . str_replace(".", "-", $_SERVER['REMOTE_ADDR']);

        $now = date("Y-m-d H:i:s");

        // Let's see if the token is valid, and it's been requested within two hours...
        $query = "SELECT * FROM " . $this->usertable . " WHERE shadowtoken = ? AND shadowvalidity > ?";
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
        $user = $this->db->executeQuery($query, array($token, $now), array(\PDO::PARAM_STR))->fetch();

        if (!empty($user)) {

            // allright, we can reset this user..
            $this->app['session']->getFlashBag()->set('success', "Password reset successful! You can now log on with the password that was sent to you via email.");

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
            $this->app['session']->getFlashBag()->set('error', "Password reset not successful! Either the token was incorrect, or you were too late, or you tried to reset the password from a different IP-address.");


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

        // Remove all auth tokens when logging off a user (so we sign out _all_ this user's sessions on all locations)
        $this->db->delete($this->authtokentable, array('username' => $this->currentuser['username']));

        // Remove the cookie..
        setcookie('bolt_authtoken', '', time() -1 , '/', $this->app['config']->get('general/cookies_domain'));

        // This is commented out for now: shouldn't be necessary, and it also removes the flash notice.
        // $this->session->invalidate();

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
            'userlevel' => key($this->getUserLevels()),
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

                // get the available contenttypes.
                $allcontenttypes = array_keys($this->app['config']->get('contenttypes'));

                $tempusers = $this->db->fetchAll($query);

                foreach ($tempusers as $user) {
                    $key = $user['username'];
                    $this->users[$key] = $user;
                    $this->users[$key]['password'] = "**dontchange**";

                    // Older Bolt versions didn't store userlevel as int. Assume they're 'Developer', to prevent lockout.
                    if (in_array($this->users[$key]['userlevel'], array('administrator', 'developer', 'editor'))) {
                        $this->users[$key]['userlevel'] = self::DEVELOPER;
                    }

                    // Make sure contenttypes is an array.
                    if (!array_key_exists('contenttypes', $this->users[$key])) {
                        $this->users[$key]['contenttypes'] = "";
                    }
                    $this->users[$key]['contenttypes'] = unserialize($this->users[$key]['contenttypes']);
                    if (!is_array($this->users[$key]['contenttypes'])) {
                        $this->users[$key]['contenttypes'] = array();
                    }
                    // Intersect, to make sure no old/deleted contenttypes show up.
                    $this->users[$key]['contenttypes'] = array_intersect($this->users[$key]['contenttypes'], $allcontenttypes);

                    // Developers/admins can access all content
                    if ($this->users[$key]['userlevel'] > self::EDITOR) {
                        $this->users[$key]['contenttypes'] = $allcontenttypes;
                    }


                }
            } catch (\Exception $e) {
                // Nope. No users.
            }

            // Extra special case: if there are no users, allow adding one..
            if (empty($this->users)) {
                $this->allowed['useredit'] = self::ANONYMOUS;
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
                if ($user['id']==$id) {
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
     * @param  int        $id
     * @param  int        $enabled
     * @return bool|mixed
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
     * get an associative array of the current userlevels.
     *
     * Should we move this to a 'constants.yml' file?
     * @return array
     */
    public function getUserLevels()
    {
        $userlevels = array(
            self::EDITOR => "Editor",
            self::ADMIN => "Administrator",
            self::DEVELOPER => "Developer"
        );

        return $userlevels;

    }

    public function isAllowed($what)
    {

        if (substr($what, 0, 12) == 'contenttype:') {
            $contenttype = substr($what, 12);
            $validContenttypes = $this->users[$this->currentuser['username']]['contenttypes'];
            return (is_array($validContenttypes) && in_array($contenttype, $validContenttypes));
        }

        if (isset($this->allowed[$what]) && ($this->allowed[$what] > $this->currentuser['userlevel'])) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Check if a certain field with a certain value doesn't exist already. We use
     * 'makeSlug', because we shouldn't allow 'admin@example.org', when there already
     * is an 'ADMIN@EXAMPLE.ORG'.
     *
     * @param string $fieldname
     * @param string $value
     * @param int $currentid
     * @return bool
     */
    public function checkAvailability($fieldname, $value, $currentid=0)
    {

        foreach ($this->users as $user) {
            if ((makeSlug($user[$fieldname]) == makeSlug($value)) && ($user['id'] != $currentid)) {
                return false;
            }
        }

        // no clashes found, OK!
        return true;
    }
}
