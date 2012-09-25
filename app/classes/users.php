<?php



class Users {
  
    var $db;
    var $config;
    var $prefix;
    var $users;
    var $session;
  
    function __construct($app) {

        $this->db = $app['db'];
        $this->config = $app['config'];
        $this->prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "bolt_";
        $this->users = array();
        $this->session = $app['session'];

    }
      
    
    public function saveUser($user) {
             
        $tablename = $this->prefix . "users";
        
        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array('id', 'username', 'password', 'email', 'lastseen', 'lastip', 'displayname', 'userlevel', 'enabled');
        
        // unset columns we don't need to store..
        foreach($user as $key => $value) {
            if (!in_array($key, $allowedcolumns)) {
                unset($user[$key]);
            }
        }
        
        if (!empty($user['password'])) {
           require_once(__DIR__."/phpass/PasswordHash.php");
           $hasher = new PasswordHash(8, TRUE);
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
            return $this->db->insert($tablename, $user);
        } else {
            return $this->db->update($tablename, $user, array('id' => $user['id']));
        }
        
    }      
    
    
    public function deleteUser($id) {
        
        $tablename = $this->prefix . "users";
        
        $user = $this->getUser($id);
        
        if (empty($user['id'])) {
            $this->session->setFlash('error', 'That user does not exist.');    
            return false;
        } else {
            return $this->db->delete($tablename, array('id' => $user['id']));
        }
        
    }
    
        
    public function login($user, $password) {
     
        $userslug = makeSlug($user);

        $tablename = $this->prefix . "users";

        // for once we don't use getUser(), because we need the password.
        $user = $this->db->fetchAssoc("SELECT * FROM $tablename WHERE username='$userslug'");

        if (empty($user)) {
            $this->session->setFlash('error', 'Username or password not correct. Please check your input.');    
            return false;
        }
        
        require_once(__DIR__."/phpass/PasswordHash.php");
        $hasher = new PasswordHash(8, TRUE);
       
        if ($hasher->CheckPassword($password, $user['password'])) {

            if (!$user['enabled']) {
                $this->session->setFlash('error', 'Your account is disabled. Sorry about that.');    
                return false;
            }

            $update = array(
                'lastseen' => date('Y-m-d H:i:s'),
                'lastip' => $_SERVER['REMOTE_ADDR']
            );

            $this->db->update($tablename, $update, array('id' => $user['id']));

            $user = $this->getUser($user['id']);

            $this->session->start();
            $this->session->set('user', $user);
            $this->session->setFlash('success', "You've been logged on successfully.");    
            
            return true;
            
        } else {      
            $this->session->setFlash('error', 'Username or password not correct. Please check your input.');    
            return false;
        }
        
        
    }
        
        
    public function getEmptyUser() {
        
        $user = array(
            'id' => '',
            'username' => '',
            'password' => ''
        );
        
        
        return $user;
        
    
        
        
    }
    
    public function getUsers() {
        global $app;

        if (empty($this->users) || !is_array($this->users)) {

            // $app['log']->add('Users: getUsers()', 1);

            $tablename = $this->prefix . "users";
            $query = "SELECT * FROM $tablename";
            $this->users = array();

            try {
                $tempusers = $this->db->fetchAll($query);

                foreach($tempusers as $user) {
                    $key = $user['username'];
                    $this->users[$key] = $user;
                    $this->users[$key]['password'] = "**dontchange**";
                }
            } catch (Exception $e) {
                // Nope. No users.
            }

        }

        return $this->users;

    }
    
    
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