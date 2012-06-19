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
      
    
    protected function insertContent($content, $contenttype, $allowedcolumns) {
        
        $tablename = $this->prefix . $contenttype;
        
        $content['datecreated'] = date('Y-m-d H:i:s');
        
        
        // unset columns we don't need to store..
        foreach($content as $key => $value) {
            if (!in_array($key, $allowedcolumns)) {
                unset($content[$key]);
            }
        }
        
        return $this->db->insert($tablename, $content);
        
    }
    
    
    protected function updateContent($content, $contenttype, $allowedcolumns) {

        $tablename = $this->prefix . $contenttype;
        
        // unset columns we don't need to store..
        foreach($content as $key => $value) {
            if (!in_array($key, $allowedcolumns)) {
                unset($content[$key]);
            }
        }
        unset($content['datecreated']);
        

        return $this->db->update($tablename, $content, array('id' => $content['id']));
        
    }
        
        
    public function getEmptyUser() {
        
        $contenttype = $this->getContentType($contenttype);
        
        $content = array(
            'id' => '',
            'slug' => '',
            'datecreated' => '',
            'datechanged' => '',
            'username' => '',
            'status' => ''
        );
        
        
        foreach ($contenttype['fields'] as $key => $field) {
            $content[$key] = '';
        }
        
        return $content;
        
    
        
        
    }
    
    public function getUsers() {
               
        $tablename = $this->prefix . "users";

        $query = "SELECT * FROM $tablename";
        
        $users = $this->db->fetchAll($query);
        
        return $users;
        
        
    }
        
  
}