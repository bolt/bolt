<?php

namespace Bolt\Database;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\TableDiff;

class IntegrityChecker
{
    /**
     * @var \Bolt\Application
     */
    private $app;
    /**
     * @var string
     */
    private $prefix;
    /**
     * Default value for TEXT fields, differs per platform
     * @var string|null
     */
    private $textDefault = null;

    public function __construct(\Bolt\Application $app)
    {
        $this->app = $app;

        $this->prefix = $this->app['config']->get('general/database/prefix' , "bolt_");

        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }

        // Check the table integrity only once per hour, per session. (since it's pretty time-consuming.
        $this->checktimer = 3600;

        if($this->app['db']->getDatabasePlatform() instanceof SqlitePlatform) {
            $this->textDefault = '';
        }

    }

    /**
     * Get an associative array with the bolt_tables tables as Doctrine\DBAL\Schema\Table objects
     *
     * @return array
     */
    protected function getTableObjects()
    {

        $sm = $this->app['db']->getSchemaManager();

        $tables = array();

        foreach ($sm->listTables() as $table) {
            if ( strpos($table->getName(), $this->prefix) === 0 ) {
                $tables[ $table->getName() ] = $table;
                // $output[] = "Found table <tt>" . $table->getName() . "</tt>.";
            }
        }

        return $tables;

    }

    /**
     * Check if just the users table is present.
     *
     * @return boolean
     */
    public function checkUserTableIntegrity()
    {

        $tables = $this->getTableObjects();

        // Check the users table..
        if (!isset($tables[$this->prefix."users"])) {
            return false;
        }

        return true;

    }

    /**
     * Check if all required tables and columns are present in the DB
     *
     * @return array Messages with errors, if any
     */
    public function checkTablesIntegrity()
    {

        $messages = array();

        $currentTables = $this->getTableObjects();

        $comparator = new Comparator();

        $baseTables = $this->getBoltTablesNames();

        $tables = $this->getTablesSchema();

        /** @var $table Table */
        foreach($tables as $table) {
            // Create the users table..
            if (!isset($currentTables[$table->getName()])) {

                $messages[] = "Table <tt>" . $table->getName() . "</tt> is not present.";

            } else {

                $diff = $comparator->diffTable( $currentTables[$table->getName()], $table );
                if ( $diff ) {
                    $diff = $this->cleanupTableDiff($diff);

                    // diff may be just deleted columns which we have reset above
                    // only exec and add output if does really alter anything
                    if ($this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
                        $msg = "Table <tt>" . $table->getName() . "</tt> is not the correct schema: ";
                        $msgParts = array();
                        // No check on foreign keys yet because we don't use them
                        /** @var $col Column */
                        foreach( $diff->addedColumns as $col ) {
                            $msgParts[] = "missing column <tt>" . $col->getName() . "</tt>";
                        }
                        /** @var $index Index */
                        foreach( $diff->addedIndexes as $index ) {
                            $msgParts[] = "missing index on <tt>" . implode( ', ', $index->getUnquotedColumns() ) . "</tt>";
                        }
                        ///** @var $fk ForeignKeyConstraint */
                        //foreach( $diff->addedForeignKeys as $fk ) {
                        //    $msgParts[] = "missing foreign key <tt>" . $fk->getName() . "</tt>";
                        //}
                        /** @var $col ColumnDiff */
                        foreach( $diff->changedColumns as $col ) {
                            $msgParts[] = "invalid column <tt>" . $col->oldColumnName . "</tt>";
                        }
                        /** @var $index Index */
                        foreach( $diff->changedIndexes as $index ) {
                            $msgParts[] = "invalid index on <tt>" . implode( ', ', $index->getUnquotedColumns() ) . "</tt>";
                        }
                        ///** @var $fk ForeignKeyConstraint */
                        //foreach( $diff->changedForeignKeys as $fk ) {
                        //    $msgParts[] = "invalid foreign key " . $fk->getName() . "</tt>";
                        //}
                        foreach( $diff->removedColumns as $colName => $val ) {
                            $msgParts[] = "removed column <tt>" . $colName . "</tt>";
                        }
                        foreach( $diff->removedIndexes as $indexName => $val ) {
                            $msgParts[] = "removed index <tt>" . $indexName . "</tt>";
                        }
                        //foreach( $diff->removedForeignKeys as $fkName => $val ) {
                        //    $msgParts[] = "removed foreign key <tt>" . $fkName . "</tt>";
                        //}
                        $msg .= implode( ', ', $msgParts );
                        $messages[] = $msg;
                    }
                }
            }
        }

        // If there were no messages, update the timer, so we don't check it again..
        // If there _are_ messages, keep checking until it's fixed.
        if (empty($messages)) {
            $this->app['session']->set('database_checked', time());
        }

        return $messages;
    }

    /**
     * Determine if we need to check the table integrity. Do this only once per hour, per session, since it's pretty
     * time consuming.
     *
     * @return boolean Check needed
     */
    public function needsCheck()
    {

        // Only check the DB once an hour, because it's pretty time-consuming.
        $databasechecked = time() - $this->app['session']->get('database_checked');

        if ($databasechecked < $this->checktimer) {
            return false;
        } else {
            return true;
        }

    }


    /**
     * Check and repair tables
     *
     * @return array
     */
    public function repairTables()
    {

        // When repairing tables we want to start with an empty flashbag. Otherwise we get another
        // 'repair your DB'-notice, right after we're done repairing.
        $this->app['session']->getFlashBag()->clear();

        $output = array();

        $currentTables = $this->getTableObjects();

        /** @var $schemaManager AbstractSchemaManager */
        $schemaManager = $this->app['db']->getSchemaManager();

        $comparator = new Comparator();

        $baseTables = $this->getBoltTablesNames();
        $tables = $this->getTablesSchema();

        /** @var $table Table */
        foreach($tables as $table) {
            // Create the users table..
            if (!isset($currentTables[$table->getName()])) {

                /** @var $platform AbstractPlatform */
                $platform = $this->app['db']->getDatabasePlatform();
                $queries = $platform->getCreateTableSQL($table);
                foreach($queries as $query) {
                    $this->app['db']->query($query);
                }

                $output[] = "Created table <tt>" . $table->getName() . "</tt>.";

            } else {

                $diff = $comparator->diffTable( $currentTables[$table->getName()], $table );
                if ($diff) {
                    $diff = $this->cleanupTableDiff($diff);
                    // diff may be just deleted columns which we have reset above
                    // only exec and add output if does really alter anything
                    if ($this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
                        $schemaManager->alterTable( $diff );
                        $output[] = "Updated <tt>" . $table->getName() . "</tt> table to match current schema.";
                    }
                }
            }
        }

        return $output;

    }

    /**
     * Cleanup a table diff, remove changes we want to keep or fix platform specific issues
     *
     * @param TableDiff $diff
     * @return TableDiff
     */
    protected function cleanupTableDiff(TableDiff $diff) {

        $baseTables = $this->getBoltTablesNames();

        if (!in_array($diff->fromTable->getName(),$baseTables)) {
            // we don't remove fields from contenttype tables to prevent accidental data removal
            if ($diff->removedColumns) {
                //var_dump($diff->removedColumns);
                /** @var $column Column */
                foreach($diff->removedColumns as $column) {
                    //$output[] = "<i>Field <tt>" . $column->getName() . "</tt> in <tt>" . $table->getName() . "</tt> " .
                    //    "is no longer defined in the config, delete manually if no longer needed.</i>";
                }
            }
            $diff->removedColumns = array();
        }

        return $diff;
    }

    /**
     * @return array
     */
    protected function getTablesSchema() {

        $schema = new Schema();
        return array_merge( $this->getBoltTablesSchema($schema), $this->getContentTypeTablesSchema($schema) );
    }

    /**
     * @return array
     */
    protected function getBoltTablesNames() {
        $baseTables = array();
        /** @var $table Table */
        foreach($this->getBoltTablesSchema(new Schema()) as $table) {
            $baseTables[] = $table->getName();
        }
        return $baseTables;
    }

    /**
     * @param Schema $schema
     * @return array
     */
    protected function getBoltTablesSchema(Schema $schema) {

        $tables = array();

        $authtokenTable = $schema->createTable($this->prefix."authtoken");
        $authtokenTable->addColumn("id", "integer", array('autoincrement' => true));
        $authtokenTable->setPrimaryKey(array("id"));
        $authtokenTable->addColumn("username", "string", array("length" => 32));
        $authtokenTable->addIndex( array( 'username' ) );
        $authtokenTable->addColumn("token", "string", array("length" => 128));
        $authtokenTable->addColumn("salt", "string", array("length" => 128));
        $authtokenTable->addColumn("lastseen", "datetime", array("default" => "1900-01-01 00:00:00"));
        $authtokenTable->addColumn("ip", "string", array("length" => 32, "default" => ""));
        $authtokenTable->addColumn("useragent", "string", array("length" => 128, "default" => ""));
        $authtokenTable->addColumn("validity", "datetime", array("default" => "1900-01-01 00:00:00"));
        $tables[] = $authtokenTable;

        $usersTable = $schema->createTable($this->prefix."users");
        $usersTable->addColumn("id", "integer", array('autoincrement' => true));
        $usersTable->setPrimaryKey(array("id"));
        $usersTable->addColumn("username", "string", array("length" => 32));
        $usersTable->addIndex( array( 'username' ) );
        $usersTable->addColumn("password", "string", array("length" => 128));
        $usersTable->addColumn("email", "string", array("length" => 128));
        $usersTable->addColumn("lastseen", "datetime");
        $usersTable->addColumn("lastip", "string", array("length" => 32, "default" => ""));
        $usersTable->addColumn("displayname", "string", array("length" => 32));
        $usersTable->addColumn("userlevel", "string", array("length" => 32));
        $usersTable->addColumn("contenttypes", "string", array("length" => 256));
        $usersTable->addColumn("enabled", "boolean");
        $usersTable->addIndex( array( 'enabled' ) );
        $usersTable->addColumn("shadowpassword", "string", array("length" => 128, "default" => ""));
        $usersTable->addColumn("shadowtoken", "string", array("length" => 128, "default" => ""));
        $usersTable->addColumn("shadowvalidity", "datetime", array("default" => "1900-01-01 00:00:00"));
        $usersTable->addColumn("failedlogins", "integer", array("default" => 0));
        $usersTable->addColumn("throttleduntil", "datetime", array("default" => "1900-01-01 00:00:00"));
        $tables[] = $usersTable;

        $taxonomyTable = $schema->createTable($this->prefix."taxonomy");
        $taxonomyTable->addColumn("id", "integer", array('autoincrement' => true));
        $taxonomyTable->setPrimaryKey(array("id"));
        $taxonomyTable->addColumn("content_id", "integer");
        $taxonomyTable->addIndex( array( 'content_id' ) );
        $taxonomyTable->addColumn("contenttype", "string", array("length" => 32));
        $taxonomyTable->addIndex( array( 'contenttype' ) );
        $taxonomyTable->addColumn("taxonomytype", "string", array("length" => 32));
        $taxonomyTable->addIndex( array( 'taxonomytype' ) );
        $taxonomyTable->addColumn("slug", "string", array("length" => 64));
        $taxonomyTable->addColumn("name", "string", array("length" => 64, "default" => ""));
        $taxonomyTable->addColumn("sortorder", "integer", array("default" => 0));
        $taxonomyTable->addIndex( array( 'sortorder' ) );
        $tables[] = $taxonomyTable;

        $relationsTable = $schema->createTable($this->prefix."relations");
        $relationsTable->addColumn("id", "integer", array('autoincrement' => true));
        $relationsTable->setPrimaryKey(array("id"));
        $relationsTable->addColumn("from_contenttype", "string", array("length" => 32));
        $relationsTable->addIndex( array( 'from_contenttype' ) );
        $relationsTable->addColumn("from_id", "integer");
        $relationsTable->addIndex( array( 'from_id' ) );
        $relationsTable->addColumn("to_contenttype", "string", array("length" => 32));
        $relationsTable->addIndex( array( 'to_contenttype' ) );
        $relationsTable->addColumn("to_id", "integer");
        $relationsTable->addIndex( array( 'to_id' ) );
        $tables[] = $relationsTable;

        $logTable = $schema->createTable($this->prefix."log");
        $logTable->addColumn("id", "integer", array('autoincrement' => true));
        $logTable->setPrimaryKey(array("id"));
        $logTable->addColumn("level", "integer");
        $logTable->addIndex( array( 'level' ) );
        $logTable->addColumn("date", "datetime");
        $logTable->addIndex( array( 'date' ) );
        $logTable->addColumn("message", "string", array("length" => 1024));
        $logTable->addColumn("username", "string", array("length" => 64, "default" => ""));
        $logTable->addIndex( array( 'username' ) );
        $logTable->addColumn("requesturi", "string", array("length" => 128));
        $logTable->addColumn("route", "string", array("length" => 128));
        $logTable->addColumn("ip", "string", array("length" => 32, "default" => ""));
        $logTable->addColumn("file", "string", array("length" => 128, "default" => ""));
        $logTable->addColumn("line", "integer");
        $logTable->addColumn("contenttype", "string", array("length" => 32));
        $logTable->addColumn("content_id", "integer");
        $logTable->addColumn("code", "string", array("length" => 32));
        $logTable->addIndex( array( 'code' ) );
        $logTable->addColumn("dump", "string", array("length" => 1024));
        $tables[] = $logTable;

        return $tables;
    }

    /**
     * @param Schema $schema
     * @return array
     */
    protected function getContentTypeTablesSchema(Schema $schema) {

        $dboptions = $this->app['config']->getDBOptions();

        $tables = array();

        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->app['config']->get('contenttypes') as $key => $contenttype) {

            // create the table if necessary..
            $tablename = $this->getTablename($key);

            $myTable = $schema->createTable($tablename);
            $myTable->addColumn("id", "integer", array('autoincrement' => true));
            $myTable->setPrimaryKey(array("id"));
            $myTable->addColumn("slug", "string", array("length" => 128));
            $myTable->addIndex( array( 'slug' ) );
            $myTable->addColumn("datecreated", "datetime");
            $myTable->addIndex( array( 'datecreated' ) );
            $myTable->addColumn("datechanged", "datetime");
            $myTable->addIndex( array( 'datechanged' ) );
            $myTable->addColumn("datepublish", "datetime");
            $myTable->addIndex( array( 'datepublish' ) );
            $myTable->addColumn("datedepublish", "datetime", array("default" => "1900-01-01 00:00:00"));
            $myTable->addIndex( array( 'datedepublish' ) );
            $myTable->addColumn("username", "string", array("length" => 32));
            $myTable->addColumn("status", "string", array("length" => 32));
            $myTable->addIndex( array( 'status' ) );

            // Check if all the fields are present in the DB..
            foreach ($contenttype['fields'] as $field => $values) {

                if (in_array($field, $dboptions['reservedwords'])) {
                    $error = sprintf("You're using '%s' as a field name, but that is a reserved word in %s. Please fix it, and refresh this page.",
                        $field,
                        $dboptions['driver']
                    );
                    $this->app['session']->getFlashBag()->set('error', $error);
                    continue;
                }

                switch ($values['type']) {
                    case 'text':
                    case 'templateselect':
                    case 'image':
                    case 'file':
                        $myTable->addColumn($field, "string", array("length" => 256, "default" => ""));
                        break;
                    case 'float':
                        $myTable->addColumn($field, "float", array("default" => 0));
                        break;
                    case 'number': // deprecated..
                        $myTable->addColumn($field, "decimal", array("precision" => "18", "scale" => "9", "default" => 0));
                        break;
                    case 'integer':
                        $myTable->addColumn($field, "integer", array("default" => 0));
                        break;
                    case 'checkbox':
                        $myTable->addColumn($field, "boolean", array("default" => 0));
                        break;
                    case 'html':
                    case 'textarea':
                    case 'video':
                    case 'markdown':
                    case 'geolocation':
                    case 'imagelist':
                    case 'select':
                        $myTable->addColumn($field, "text", array("default" => $this->textDefault));
                        break;
                    case 'datetime':
                        $myTable->addColumn($field, "datetime");
                        break;
                    case 'date':
                        $myTable->addColumn($field, "date");
                        break;
                    case 'slug':
                    case 'id':
                    case 'datecreated':
                    case 'datechanged':
                    case 'datepublish':
                    case 'datedepublish':
                    case 'username':
                    case 'status':
                        // These are the default columns. Don't try to add these.
                        break;
                    default:
                        $output[] = "Type <tt>" . $values['type'] . "</tt> is not a correct field type for field <tt>$field</tt> in table <tt>$tablename</tt>.";
                }

                if (isset($values['index']) && $values['index'] == 'true') {
                    $myTable->addIndex( array( $field ) );
                }

            }
            $tables[] = $myTable;

        }
        return $tables;
    }

    /**
     * Get the tablename with prefix from a given $name
     *
     * @param $name
     * @return mixed
     */
    protected function getTablename($name)
    {

        $name = str_replace("-", "_", makeSlug($name));
        $tablename = sprintf("%s%s", $this->prefix, $name);
        return $tablename;

    }
}
