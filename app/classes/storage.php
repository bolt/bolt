<?php



class Storage {
  
    var $db;
    var $config;
    var $prefix;
  
    function __construct($app) {
    
        $this->db = $app['db'];
        $this->config = $app['config'];
        
        $this->prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "pilex_";
        
    }
  
    /** 
     * Check if just the users table is present.
     *
     * @return boolean
     */ 
    function checkUserTableIntegrity() {
        
        $sm = $this->db->getSchemaManager();

        $tables = $this->getTables();
        
        // Check the users table..
        if (!isset($tables[$this->prefix."users"])) {
            return false;            
        }
        
        return true;    
        
    }
  
  
  
    /**
     * Check if all required tables and columns are present in the DB
     *
     * @return boolean
     */
    function checkTablesIntegrity() {
        
        $sm = $this->db->getSchemaManager();

        $tables = $this->getTables();
        
        // Check the users table..
        if (!isset($tables[$this->prefix."users"])) {
            return false;            
        }
        
        
        
        // Check the taxonomy table..
        if (!isset($tables[$this->prefix."taxonomy"])) {
            return false;              
        }
        
        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->config['contenttypes'] as $key => $contenttype) {

            $tablename = $this->prefix . makeSlug($key);
            
            if (!isset($tables[$tablename])) {
                return false;  
            }
            
            // Check if all the fields are present in the DB..
            foreach($contenttype['fields'] as $field => $values) {
                if (!isset($tables[$tablename][$field])) {
                    return false;
                }
            }
            
        }

         
        return true;    
        
    }
  
  
    function repairTables() {
      
        $sm = $this->db->getSchemaManager();
      
        $output = array();

        $tables = $this->getTables();
        
        // Check the users table..
        if (!isset($tables[$this->prefix."users"])) {

            $schema = new \Doctrine\DBAL\Schema\Schema();
            $myTable = $schema->createTable($this->prefix."users"); 
            $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
            $myTable->setPrimaryKey(array("id"));
            $myTable->addColumn("username", "string", array("length" => 32));
            $myTable->addColumn("password", "string", array("length" => 64));
            $myTable->addColumn("email", "string", array("length" => 64));
            $myTable->addColumn("lastseen", "datetime");                        
            $myTable->addColumn("lastip", "string", array("length" => 32));
            $myTable->addColumn("displayname", "string", array("length" => 32));
            $myTable->addColumn("userlevel", "string", array("length" => 32));
            $myTable->addColumn("enabled", "boolean");
            
            $queries = $schema->toSql($this->db->getDatabasePlatform());
            $queries = implode("; ", $queries);
            $this->db->query($queries);
            
            // echo "<pre>\n" . print_r($queries, true) . "</pre>\n";
            
            $output[] = "Created table <tt>" . $this->prefix."users" . "</tt>.";
            
        }
        
         
        // Check the taxonomy table..
        if (!isset($tables[$this->prefix."taxonomy"])) {

            $schema = new \Doctrine\DBAL\Schema\Schema();
            $myTable = $schema->createTable($this->prefix."taxonomy"); 
            $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
            $myTable->setPrimaryKey(array("id"));
            $myTable->addColumn("content_id", "integer", array("unsigned" => true));
            $myTable->addColumn("contenttype", "string", array("length" => 32));
            $myTable->addColumn("taxonomytype", "string", array("length" => 32));
            $myTable->addColumn("slug", "datetime", array("length" => 64));   
            $myTable->addColumn("name", "datetime", array("length" => 64));                        
            
            $queries = $schema->toSql($this->db->getDatabasePlatform());
            $queries = implode("; ", $queries);
            $this->db->query($queries);
            
            $output[] = "Created table <tt>" . $this->prefix."taxonomy" . "</tt>.";
            
        }
        
        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->config['contenttypes'] as $key => $contenttype) {

            // create the table if necessary.. 
            $tablename = $this->prefix . makeSlug($key);
            
            if (!isset($tables[$tablename])) {
                
                $schema = new \Doctrine\DBAL\Schema\Schema();
                $myTable = $schema->createTable($tablename); 
                $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
                $myTable->setPrimaryKey(array("id"));
                $myTable->addColumn("slug", "string", array("length" => 128));
                $myTable->addColumn("datecreated", "datetime");    
                $myTable->addColumn("datechanged", "datetime"); 
                $myTable->addColumn("username", "string", array("length" => 32));
                $myTable->addColumn("status", "string", array("length" => 32));


                $queries = $schema->toSql($this->db->getDatabasePlatform());
                $queries = implode("; ", $queries);
                $this->db->query($queries);
                
                $output[] = "Created table <tt>" . $tablename . "</tt>.";
                
            }
            
            // Check if all the fields are present in the DB..
            foreach($contenttype['fields'] as $field => $values) {
                
                if (!isset($tables[$tablename][$field])) { 
          
                    $myTable = $sm->listTableDetails($tablename);
            
                    switch($values['type']) {
                        
                        case 'text':
                        case 'templateselect':
                        case 'image':
                            $query = sprintf("ALTER TABLE `%s` ADD `%s` VARCHAR( 256 ) NOT NULL", $tablename, $field);
                            $this->db->query($query);
                            $output[] = "Added column <tt>" . $field . "</tt> to table <tt>" . $tablename . "</tt>.";
                            break;
                            
                        case 'html':
                        case 'textarea':
                            $query = sprintf("ALTER TABLE `%s` ADD `%s` TEXT NOT NULL", $tablename, $field);
                            $this->db->query($query);
                            $output[] = "Added column <tt>" . $field . "</tt> to table <tt>" . $tablename . "</tt>.";
                            break;
                            
                        case 'datetime':
                        case 'date':
                            $query = sprintf("ALTER TABLE `%s` ADD `%s` DATETIME", $tablename, $field);
                            $this->db->query($query);
                            $output[] = "Added column <tt>" . $field . "</tt> to table <tt>" . $tablename . "</tt>.";
                            break; 
                            
                        case 'slug':
                        case 'id':
                        case 'datecreated':
                        case 'datechanged':
                        case 'username':
                            // These are the default columns. Don't try to add these. 
                            break;
                        
                        default: 
                            $output[] = "Type <tt>" .  $values['type'] . "<tt> is not a correct field type for field <tt>$field</tt> in table <tt>$tablename<tt>.";
                        
                    }
                
                
                }


            }
            
            
        }
        
        return $output;
      
    }
    
    public function preFill() {

        $this->guzzleclient = new Guzzle\Service\Client('http://loripsum.net/api/');

        $output = "";
        
        foreach ($this->config['contenttypes'] as $key => $contenttype) {
            
            $amount = isset($contenttype['prefill']) ? $contenttype['prefill'] : 5;
        
            for($i=1; $i<= $amount; $i++) {
                $output .= $this->preFillSingle($key, $contenttype);
            }
            
            
        }
        
        
        $output .= "\n\nDone!";
        
        return $output;
        
    }
    
    private function preFillSingle($key, $contenttype) {
           
        $slug = makeSlug($key);
        $tablename = $this->prefix . $slug;

        
        $content = array();
        $title = "";
        
        $content['contenttype'] = $key;
        $content['datecreated'] = date('Y-m-d H:i:s', time() - rand(0, 365*24*60*60));
        

        //todo: fix this, use a random name.
        $content['username'] = "admin";

        switch(rand(1,8)) {
            case 1: 
                $content['status'] = "timed";
                break;
            case 2: 
                $content['status'] = "draft";
                break;
            case 3: 
                $content['status'] = "depublished";
                break;
            default:
                $content['status'] = "published";
                break;
        }

        foreach($contenttype['fields'] as $field => $values) {
            
            switch($values['type']) {
                    
                case 'text':
                    $content[$field] = trim(strip_tags($this->guzzleclient->get('1/veryshort')->send()->getBody(true)));
                    if (empty($title)) { $title = $content[$field]; }
                    break;
                    
                case 'image':
                    // todo: make something clever for this.
                    break;
                    
                case 'html':
                case 'textarea':
                    if (in_array($field, array('teaser', 'introduction', 'excerpt', 'intro'))) {
                        $params = 'medium/decorate/link/1';
                    } else {
                        $params = 'medium/decorate/link/ol/ul/3';
                        //$params = 'long/1';

                    }
                    $content[$field] = trim($this->guzzleclient->get($params)->send()->getBody(true));
                    break;
                    
                case 'datetime':
                case 'date':
                    $content[$field] = date('Y-m-d H:i:s', time() - rand(-365*24*60*60, 365*24*60*60));
                    break; 
                    
            }
            
            
            
        }

        $this->saveContent($content);        
        
        $output = "Added to <tt>$key</tt> '" .$content['title'] . "'<br>\n";
        
        return $output;
        
    }
    
    
    public function saveContent($content, $contenttype="") {
              
        if (empty($contenttype) && !empty($content['contenttype'])) {
            $contenttype = $content['contenttype'];
        }
       
        if (empty($contenttype)) {
            echo "Contenttype is required.";
            return false;
        }
        
        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array('id', 'slug', 'datecreated', 'datechanged', 'username', 'status');
        // add the fields for this contenttype, 
        foreach ($this->config['contenttypes'][$contenttype]['fields'] as $key => $values) {

            $allowedcolumns[] = $key;
            
            // Set the slug, while we're at it..
            if ($values['type'] == "slug" && !empty($values['uses']) && empty($content['slug'])) {              
                $content['slug'] = makeSlug($content[ $values['uses'] ]);
            } 
            
        }
        
        // Set datechanged
        $content['datechanged'] = date('Y-m-d H:i:s');
        
        // Decide whether to insert a new record, or update an existing one.
        
        
        if (empty($content['id'])) {
            return $this->insertContent($content, $contenttype, $allowedcolumns);
        } else {
            return $this->updateContent($content, $contenttype, $allowedcolumns);
        }
        
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
        
        
    public function getEmptyContent($contenttype) {
        
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
    
    public function getContent($contenttype, $parameters) {
               
        $limit = !empty($parameters['limit']) ? $parameters['limit'] : 10;
        
        $slug = makeSlug($contenttype);
        $tablename = $this->prefix . $slug;

        $query = "SELECT * FROM $tablename";
        
        // Order 
        if (!empty($parameters['order'])) {
            $query .= " ORDER BY " . safeString($parameters['order']);
        }
        
        // Where 
        if (!empty($parameters['where'])) {
            $query .= " WHERE " . $parameters['where'];
        }        
        
        // Add the limit
        $query .= " LIMIT $limit";

        $content = $this->db->fetchAll($query);
        
        return $content;
        
        
    }
    
    /**
     * Get a single unit of content:
     * 
     * examples: 
     * $content = $app['storage']->getSingleContent("page/1");
     * $content = $app['storage']->getSingleContent("entry", array('where' => "slug = 'lorem-ipsum'"));
     * $content = $app['storage']->getSingleContent($contenttype['slug'], array('where' => "id = '$slug'"));
     *
     */
    public function getSingleContent($contenttype, $parameters=array()) {
                
        // Special case: if $contenttype has a slash, like 'entry/1', we'll assume we need to get entry #1. 
        if (strpos($contenttype, "/")>0) {
            list ($contenttype, $id) = explode("/", $contenttype);
            
            
            
            if (is_numeric($id)) {
               $parameters = array('where' => "id = ".$id);
            } else {
               $parameters = array('where' => "slug = ".makeSlug($id));
            }
        }
        
        $contenttype = $this->getContentType($contenttype);
        
        // Make sure limit is 1
        $parameters['limit'] = 1;
        
        $result = $this->getContent($contenttype['slug'], $parameters);
    
        
        if (isset($result[0])) {
            return $result[0];
        } else {
            return false;
        }
        
    }
        
    
    
    public function getContentType($contenttypeslug) {
    
    
        $contenttypeslug = makeSlug($contenttypeslug);

        // Return false if empty, can't find it..
        if (empty($contenttypeslug)) {
            return false;
        }  
        
        // See if we've either given the correct contenttype, or try to find it by name or singular_name.
        if (isset($this->config['contenttypes'][$contenttypeslug])) {
            $contenttype = $this->config['contenttypes'][$contenttypeslug];
        } else {
            foreach($this->config['contenttypes'] as $key => $ct) {
                if ($contenttypeslug == makeSlug($ct['singular_name']) || $contenttypeslug == makeSlug($ct['name'])) {
                    $contenttype = $this->config['contenttypes'][$key];
                }
            }
            
        }
    
        if (!empty($contenttype)) {
    
            $contenttype['slug'] = makeSlug($contenttype['name']);
            $contenttype['singular_slug'] = makeSlug($contenttype['singular_name']);
    
            return $contenttype;
        
        } else {
            return false;
        }
    
    
    }
    

    
    /**
     * Get an associative array with the pilex_tables tables and columns in the DB.
     *
     * @return array
     */
    protected function getTables() {
        
        $sm = $this->db->getSchemaManager();

        $tables = array();        
        
        foreach ($sm->listTables() as $table) {
            if ( strpos($table->getName(), $this->prefix) == 0 ) {
                foreach ($table->getColumns() as $column) {
                    $tables[ $table->getName() ][ $column->getName() ] = $column->getType(); 
                }
                // $output[] = "Found table <tt>" . $table->getName() . "</tt>.";
            }
        }
        
        return $tables;
        
    }
  
    
  
}