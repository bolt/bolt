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
        $this->prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "pilex_";
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
     
        $user = makeSlug($user);
        $user = $this->getUser($user, true);
        
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
                
            $tablename = $this->prefix . "users";
            
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
               
        $tablename = $this->prefix . "users";

        $query = "SELECT * FROM $tablename";
        
        $users = $this->db->fetchAll($query);
        
        foreach($users as $key => $user) {
            $users[$key]['password'] = "**dontchange**";
        }
        
        return $users;
        
        
    }
    
    
    public function getUser($id, $includepassword=false) {
               
        $tablename = $this->prefix . "users";

        if (is_numeric($id)) {
           $user = $this->db->fetchAssoc("SELECT * FROM $tablename where id = :id", array("id" => $id));
        } else {
           $user = $this->db->fetchAssoc("SELECT * FROM $tablename where username = :username", array("username" => $id));
        }
        
        if (!$includepassword) {
            unset($user['password']);
        }
        
        return $user;
        
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