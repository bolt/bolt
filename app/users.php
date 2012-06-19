<?php



class Users {
  
    var $db;
    var $config;
    var $prefix;
    var $users;
  
    function __construct($app) {
    
        $this->db = $app['db'];
        $this->config = $app['config'];
        $this->prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "pilex_";
        $this->users = array();

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
           require_once(__DIR__."/classes/phpass/PasswordHash.php");
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
    
        
    public function login($user, $password) {
     
        $user = makeSlug($user);
        $user = $this->getUser($user);
        
        if (empty($user)) {
            return false;
        }
        
        require_once(__DIR__."/classes/phpass/PasswordHash.php");
        $hasher = new PasswordHash(8, TRUE);

        echo "<pre>\n" . print_r($user, true) . "</pre>\n";

       
        if ($hasher->CheckPassword($password, $user['password'])) {
            echo "ja!";
            
            $update = array(
                'lastseen' => date('Y-m-d H:i:s'),
                'lastip' => $_SERVER['REMOTE_ADDR']
            );
                
            $tablename = $this->prefix . "users";
            
            $this->db->update($tablename, $update, array('id' => $user['id']));
            
            return true;
            
        } else {
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
    
    
    public function getUser($id) {
               
        $tablename = $this->prefix . "users";

        if (is_numeric($id)) {
           $user = $this->db->fetchAssoc("SELECT * FROM $tablename where id = :id", array("id" => $id));
        } else {
            echo "[b $id ]";
           $user = $this->db->fetchAssoc("SELECT * FROM $tablename where username = :username", array("username" => $id));
        }

        
        
        return $user;
        
        
    }
        
    
    
        
    
  
}