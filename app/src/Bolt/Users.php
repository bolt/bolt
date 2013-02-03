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
    public $sessiontable;
    public $users;
    public $session;
    public $currentuser;
    public $allowed;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;

        $prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "bolt_";

        $this->db = $app['db'];
        $this->config = $app['config'];
        $this->usertable = $prefix . "users";
        $this->users = array();
        $this->session = $app['session'];

        $this->checkValidSession();

        $this->allowed = array(
            'dashboard' => self::EDITOR,
            'settings' => self::ADMIN,
            'login' => self::ANONYMOUS,
            'logout' => self::EDITOR,
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
            require_once(__DIR__."/../../classes/phpass/PasswordHash.php");
            $hasher = new \PasswordHash(8, true);
            $user['password'] = $hasher->HashPassword($user['password']);
        } else {
            unset($user['password']);
        }

        // make sure the username is slug-like
        $user['username'] = makeSlug($user['username']);

        if (!isset($user['lastseen'])) {
            $user['lastseen'] = "0000-00-00";
        }

        if (!isset($user['userlevel'])) {
            $user['userlevel'] = key(array_slice($this->getUserLevels(), -1));
        }

        if (!isset($user['enabled'])) {
            $user['enabled'] = 1;
        }

        // Serialize the contenttypes..
        $user['contenttypes'] = serialize($user['contenttypes']);

        // Decide whether to insert a new record, or update an existing one.
        if (empty($user['id'])) {
            return $this->db->insert($this->usertable, $user);
        } else {
            return $this->db->update($this->usertable, $user, array('id' => $user['id']));
        }

    }

    /**
     * We will not allow tampering with sessions, so we make sure the current session
     * is still valid for the device on which it was created, and that the username,
     * ip-address are still the same.
     *
     */
    public function checkValidSession()
    {
        if ($this->app['session']->get('user')) {
            $session = $this->app['session']->get('user');
            $database = $this->getUser($session['id']);
            $this->currentuser = array_merge($session, $database);
        } else {
            // no current user, return without doing the rest.
            return false;
        }

        if (intval($this->currentuser['userlevel']) <= self::ANONYMOUS ) {
            $this->logout();
            return false;
        }

        // set the rights for each of the contenttypes for this user.
        foreach ($this->app['config']['contenttypes'] as $key => $contenttype) {
            if (in_array($key, $this->currentuser['contenttypes'])) {
                $this->allowed['contenttype:' . $key] = self::EDITOR;
            } else {
                $this->allowed['contenttype:' . $key] = self::ADMIN;
            }
        }

        $key = $this->getSessionKey($this->currentuser['username']);

        if ($key != $this->currentuser['sessionkey']) {
            $this->app['log']->add("keys don't match. Invalidating session: $key != " . $this->currentuser['sessionkey'], 2);
            $this->app['log']->add("Automatically logged out user '".$this->currentuser['username']."': Session data didn't match.", 3, '', 'issue');
            $this->logout();
            return false;
        }

        // Check if user is _still_ allowed to log on..
        if ( ($this->currentuser['userlevel'] < self::EDITOR) || !$this->currentuser['enabled'] ) {
            $this->logout();
            return false;
        }

        return true;

    }

    /**
     * Get a key to identify the session with.
     *
     * @param  string $name
     * @return string
     */
    private function getSessionKey($name = "")
    {

        if (empty($name)) {
            return false;
        }

        $key = $name;

        if ($this->app['config']['general']['cookies_use_remoteaddr']) {
            $key .= "-". $_SERVER['REMOTE_ADDR'];
        }
        if ($this->app['config']['general']['cookies_use_browseragent']) {
            $key .= "-". $_SERVER['HTTP_USER_AGENT'];
        }
        if ($this->app['config']['general']['cookies_use_httphost']) {
            $key .= "-". $_SERVER['HTTP_HOST'];
        }

        $key = md5($key);

        return $key;

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
            $this->session->setFlash('error', 'That user does not exist.');

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
        $user = $this->db->fetchAssoc("SELECT * FROM " . $this->usertable . " WHERE username='$userslug'");

        if (empty($user)) {
            $this->session->setFlash('error', 'Username or password not correct. Please check your input.');

            return false;
        }

        require_once(__DIR__."/../../classes/phpass/PasswordHash.php");
        $hasher = new \PasswordHash(8, true);

        if ($hasher->CheckPassword($password, $user['password'])) {

            if (!$user['enabled']) {
                $this->session->setFlash('error', 'Your account is disabled. Sorry about that.');

                return false;
            }

            $update = array(
                'lastseen' => date('Y-m-d H:i:s'),
                'lastip' => $_SERVER['REMOTE_ADDR']
            );

            $this->db->update($this->usertable, $update, array('id' => $user['id']));

            $user = $this->getUser($user['id']);

            $user['sessionkey'] = $this->getSessionKey($user['username']);

            $this->session->set('user', $user);
            $this->session->setFlash('success', "You've been logged on successfully.");

            return true;

        } else {
            $this->session->setFlash('error', 'Username or password not correct. Please check your input.');

            return false;
        }

    }

    /**
     * Log out the currently logged in user.
     *
     */
    public function logout() {
        $this->session->setFlash('info', 'You have been logged out.');
        $this->session->remove('user');
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
            'enabled' => '1'
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

                    // Older Bolt versions didn't store userlevel as int. Assume they're 'Developer', to prevent lockout.
                    if (in_array($this->users[$key]['userlevel'], array('administrator', 'developer', 'editor'))) {
                        $this->users[$key]['userlevel'] = self::DEVELOPER;
                    }

                    // Make sure contenttypes is an array.
                    $this->users[$key]['contenttypes'] = unserialize($this->users[$key]['contenttypes']);
                    if (!is_array($this->users[$key]['contenttypes'])) {
                        $this->users[$key]['contenttypes'] = array();
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
            foreach ($this->users as $key => $user) {
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
    public function getCurrentUser() {

        return $this->currentuser;

    }

    /**
     * Get the username of the current user.
     *
     * @return string the username of the current user.
     */
    public function getCurrentUsername() {

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

        if (isset($this->allowed[$what]) && ($this->allowed[$what] > $this->currentuser['userlevel']) ) {
            // printf(" %s > %s ", $this->allowed[$what], $this->currentuser['userlevel']);
            return false;
        } else {
            return true;
        }

    }

}
