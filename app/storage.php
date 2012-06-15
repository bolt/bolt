<?php



class Storage {
  
    var $db;
  
    function __construct($app) {
    
        $this->db = $app['db'];
        $this->config = $app['config'];
        
    }
  
    function checkTablesIntegrity() {
        
        $sm = $this->db->getSchemaManager();
        
        $ok = true;
      
        $prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "pilex_";
        
        $tables = array();        
        
        foreach ($sm->listTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $tables[ $table->getName() ][ $column->getName() ] = $column->getType(); 
            }
        }
        
        // Check the users table..
        if (!isset($tables[$prefix."users"])) {
            return false;            
        }
        
        // Check the taxonomy table..
        if (!isset($tables[$prefix."taxonomy"])) {
            return false;              
        }
        
        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->config['contenttypes'] as $contenttype) {

            $tablename = $prefix . makeSlug($contenttype['slug']);
            
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
      
        $prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "pilex_";
        

        $tables = array();        
        
        foreach ($sm->listTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $tables[ $table->getName() ][ $column->getName() ] = $column->getType(); 
            }
        }
        
        // Check the users table..
        if (!isset($tables[$prefix."users"])) {

            $schema = new \Doctrine\DBAL\Schema\Schema();
            $myTable = $schema->createTable($prefix."users"); 
            $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
            $myTable->setPrimaryKey(array("id"));
            $myTable->addColumn("username", "string", array("length" => 32));
            $myTable->addColumn("password", "string", array("length" => 32));
            $myTable->addColumn("lastseen", "datetime");                        
            $myTable->addColumn("lastip", "string", array("length" => 32));
            $myTable->addColumn("slug", "string", array("length" => 32));
            $myTable->addColumn("displayname", "string", array("length" => 32));
            $myTable->addColumn("fullname", "string", array("length" => 64));
            
            $queries = $schema->toSql($this->db->getDatabasePlatform());
            $queries = implode("; ", $queries);
            $this->db->query($queries);
            
        }
        
        // Check the taxonomy table..
        if (!isset($tables[$prefix."taxonomy"])) {

            $schema = new \Doctrine\DBAL\Schema\Schema();
            $myTable = $schema->createTable($prefix."taxonomy"); 
            $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
            $myTable->setPrimaryKey(array("id"));
            $myTable->addColumn("content_id", "integer", array("unsigned" => true));
            $myTable->addColumn("contenttype", "string", array("length" => 32));
            $myTable->addColumn("taxonomytype", "string", array("length" => 32));
            $myTable->addColumn("value", "datetime", array("length" => 64));                        
            
            $queries = $schema->toSql($this->db->getDatabasePlatform());
            $queries = implode("; ", $queries);
            $this->db->query($queries);
            
        }
        
        
        
        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->config['contenttypes'] as $contenttype) {
            
            // echo "<pre>\n" . print_r($contenttype, true) . "</pre>\n";
            
            // create the table if necessary.. 
            $tablename = $prefix . makeSlug($contenttype['slug']);
            
            if (!isset($tables[$tablename])) {
                
                $schema = new \Doctrine\DBAL\Schema\Schema();
                $myTable = $schema->createTable($tablename); 
                $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
                $myTable->setPrimaryKey(array("id"));
                $myTable->addColumn("slug", "string", array("length" => 32));
                $myTable->addColumn("datecreated", "datetime");    
                $myTable->addColumn("datechanged", "datetime"); 
                $myTable->addColumn("username", "string", array("length" => 32));

                $queries = $schema->toSql($this->db->getDatabasePlatform());
                $queries = implode("; ", $queries);
                $this->db->query($queries);
                
                
            }
            
            // Check if all the fields are present in the DB..
            foreach($contenttype['fields'] as $field => $values) {
                
                if (!isset($tables[$tablename][$field])) { 
                    
                    //$schema = new \Doctrine\DBAL\Schema\Schema();
                    
                    //echo "[a]";
                    //$myTable = $schema->getTable($tablename); 
                    
                    $myTable = $sm->listTableDetails($tablename);
            
                    
                    switch($values['type']) {
                        
                        case 'text':
                        case 'templateselect':
                        case 'image':
                            $query = sprintf("ALTER TABLE `%s` ADD `%s` VARCHAR( 256 ) NOT NULL", $tablename, $field);
                            $this->db->query($query);
                            break;
                            
                        case 'html':
                        case 'textarea':
                            $query = sprintf("ALTER TABLE `%s` ADD `%s` VARCHAR( 16384 ) NOT NULL", $tablename, $field);
                            $this->db->query($query);
                            break;
                            
                        case 'datetime':
                        case 'date':
                            $query = sprintf("ALTER TABLE `%s` ADD `%s` DATETIME", $tablename, $field);
                            $this->db->query($query);
                            break; 
                            
                        case 'slug':
                        case 'id':
                        case 'datecreated':
                        case 'datechanged':
                        case 'username':
                            // These are the default columns. Don't try to add these. 
                            break;
                        
                            
                        default: 
                            echo "Type " .  $values['type'] . " is not a correct field type for Contenttype $tablename.";
                            die();
                        
                    }
                
                
                }


            }
            

        }
        
# possible field types:
# text - varchar(256) - input type text
# templateselect - varchar(256) - select with template filenames
# image - varchar(256) - image select/upload widget, stored as filename
# html - varchar(32768) - wysiwyg element
# textarea - varchar(32768) - <textarea>
# datetime - datetime - date and time selector widget
# date - datetime - date selector widget
        
// ALTER TABLE  `pilex_entries` ADD  `title` VARCHAR( 256 ) NOT NULL
// ALTER TABLE  `pilex_entries` ADD  `date` DATETIME NOT NULL

/*        
DROP TABLE `pilex_entries`;
DROP TABLE  `pilex_pages`;
DROP TABLE  `pilex_taxonomy`;
DROP TABLE  `pilex_users`;
*/
        
        // echo "<pre>\n" . print_r($tables, true) . "</pre>\n";
      
    }
  
  
}