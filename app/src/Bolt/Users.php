<?php

namespace Bolt;

/**
 * Class to handle things dealing with users..
 */
class Users {

    var $db;
    var $config;
    var $usertable;
    var $sessiontable;
    var $users;
    var $session;
    var $currentuser;

    function __construct($app) {

        $prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "bolt_";

        $this->db = $app['db'];
        $this->app = $app;
        $this->config = $app['config'];
        $this->usertable = $prefix . "users";
        $this->users = array();
        $this->session = $app['session'];

        $this->checkValidSession();

    }

    /**
     * Save changes to a user to the database. (re)hashing the password, if needed.
     *
     * @param array $user
     * @return mixed
     */
    public function saveUser($user) {

        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array('id', 'username', 'password', 'email', 'lastseen', 'lastip', 'displayname', 'userlevel', 'enabled');

        // unset columns we don't need to store..
        foreach($user as $key => $value) {
            if (!in_array($key, $allowedcolumns)) {
                unset($user[$key]);
            }
        }

        if (!empty($user['password']) && $user['password']!="**dontchange**") {
            require_once(__DIR__."/../../classes/phpass/PasswordHash.php");
            $hasher = new \PasswordHash(8, TRUE);
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
    public function checkValidSession() {

        if ($this->app['session']->get('user')) {
            $this->currentuser = $this->app['session']->get('user');
        } else {
            // no current user, return without doing the rest.
            //return false;
        }

        $key = $this->getSessionKey($this->currentuser['username']);

        if ($key != $this->currentuser['sessionkey']) {
            $this->app['log']->add("keys don't match. Invalidating session: $key != " . $this->currentuser['sessionkey'], 2);
            $this->app['log']->add("Automatically logged out user '".$this->currentuser['username']."': Session data didn't match.", 3, '', 'issue');
            $this->app['session']->invalidate();
            return false;
        } else {
            return true;
        }


    }

    /**
     * Get a key to identify the session with.
     *
     * @param string $name
     * @return string
     */
    private function getSessionKey($name) {

        return md5(sprintf("%s-%s-%s", $name, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_HOST']));

    }


    /**
     * Remove a user from the database.
     *
     * @param $id
     * @return bool
     */
    public function deleteUser($id) {

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
     * @param string $user
     * @param string $password
     * @return bool
     */
    public function login($user, $password) {

        $userslug = makeSlug($user);

        // for once we don't use getUser(), because we need the password.
        $user = $this->db->fetchAssoc("SELECT * FROM " . $this->usertable . " WHERE username='$userslug'");

        if (empty($user)) {
            $this->session->setFlash('error', 'Username or password not correct. Please check your input.');
            return false;
        }

        require_once(__DIR__."/../../classes/phpass/PasswordHash.php");
        $hasher = new \PasswordHash(8, TRUE);

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
     * Create a stub for a new/empty user.
     *
     * @return array
     */
    public function getEmptyUser() {

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
    public function getUsers() {
        global $app;

        if (empty($this->users) || !is_array($this->users)) {

            // $app['log']->add('Users: getUsers()', 1);

            $query = "SELECT * FROM " . $this->usertable;
            $this->users = array();

            try {
                $tempusers = $this->db->fetchAll($query);

                foreach($tempusers as $user) {
                    $key = $user['username'];
                    $this->users[$key] = $user;
                    $this->users[$key]['password'] = "**dontchange**";
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
     * @param int $id
     * @return array
     */
    public function getUser($id) {

        // Make sure we've fetched the users..
        $this->getUsers();

        if (is_numeric($id)) {
            foreach($this->users as $key => $user) {
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
     * Enable or disable a user, specified by id.
     * @param int $id
     * @param int $enabled
     * @return bool|mixed
     */
    public function setEnabled($id, $enabled=1) {

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
    public function getUserLevels() {

        $userlevels = array(
            'editor' => "Editor",
            'administrator' => "Administrator",
            'developer' => "Developer"
        );

        return $userlevels;

    }



}
