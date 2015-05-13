<?php

namespace Bolt\Database;

use Bolt\Application;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class IntegrityChecker
{
    /** @var \Bolt\Application */
    private $app;

    /** @var string */
    private $prefix;

    /** @var \Doctrine\DBAL\Schema\Table[] Current tables. */
    private $tables;

    /** @var array Array of callables that produce table definitions. */
    protected $extension_table_generators = array();

    /** @var string */
    protected $integrityCachePath;

    const INTEGRITY_CHECK_INTERVAL    = 1800; // max. validity of a database integrity check, in seconds
    const INTEGRITY_CHECK_TS_FILENAME = 'dbcheck_ts'; // filename for the check timestamp file

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        // Check the table integrity only once per hour, per session. (since it's pretty time-consuming.
        $this->checktimer = 3600;
    }

    /**
     * Invalidate our database check by removing the timestamp file from cache.
     *
     * @return void
     */
    public function invalidate()
    {
        $fileName = $this->getValidityTimestampFilename();

        // delete the cached dbcheck-ts
        if (is_writable($fileName)) {
            unlink($fileName);
        } elseif (file_exists($fileName)) {
            $message = sprintf(
                "The file '%s' exists, but couldn't be removed. Please remove this file manually, and try again.",
                $fileName
            );
            $this->app->abort(Response::HTTP_UNAUTHORIZED, $message);
        }
    }

    /**
     * Set our state as valid by writing the current date/time to the
     * app/cache/dbcheck-ts file.
     *
     * @return void
     */
    public function markValid()
    {
        $timestamp = time();
        file_put_contents($this->getValidityTimestampFilename(), $timestamp);
    }

    /**
     * Check if our state is known valid by comparing app/cache/dbcheck-ts to
     * the current timestamp.
     *
     * @return boolean
     */
    public function isValid()
    {
        if (is_readable($this->getValidityTimestampFilename())) {
            $validityTS = intval(file_get_contents($this->getValidityTimestampFilename()));
        } else {
            $validityTS = 0;
        }

        return ($validityTS >= time() - self::INTEGRITY_CHECK_INTERVAL);
    }

    /**
     * Get an associative array with the bolt_tables tables as Doctrine Table objects.
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getTableObjects()
    {
        if (!empty($this->tables)) {
            return $this->tables;
        }

        /** @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */
        $sm = $this->app['db']->getSchemaManager();

        $this->tables = array();

        foreach ($sm->listTables() as $table) {
            if (strpos($table->getName(), $this->getTablenamePrefix()) === 0) {
                $this->tables[$table->getName()] = $table;
            }
        }

        return $this->tables;
    }

    /**
     * Check if just the users table is present.
     *
     * @return boolean
     */
    public function checkUserTableIntegrity()
    {
        $tables = $this->getTableObjects();

        // Check the users table.
        if (!isset($tables[$this->getTablename('users')])) {
            return false;
        }

        return true;
    }

    /**
     * Check if all required tables and columns are present in the DB.
     *
     * @param boolean         $hinting     Return hints if true
     * @param LoggerInterface $debugLogger Debug logger
     *
     * @return array Messages with errors, if any or array(messages, hints)
     */
    public function checkTablesIntegrity($hinting = false, LoggerInterface $debugLogger = null)
    {
        $messages = array();
        $hints    = array();
        $diffs    = array();

        $currentTables = $this->getTableObjects();

        $comparator = new Comparator();

        $tables = $this->getTablesSchema();

        /** @var $table Table */
        foreach ($tables as $table) {
            // Create the users table.
            if (!isset($currentTables[$table->getName()])) {
                $messages[] = 'Table `' . $table->getName() . '` is not present.';
            } else {
                $diff = $comparator->diffTable($currentTables[$table->getName()], $table);
                if ($diff) {
                    if ($hinting && count($diff->removedColumns) > 0) {
                        $hints[] = 'In table `' . $table->getName() . '` the following fields are no ' .
                            'longer defined in the config. You could delete them manually if no longer needed: ' .
                            '`' . join('`, `', array_keys($diff->removedColumns)) . '`';
                    }

                    $diff = $this->cleanupTableDiff($diff);

                    // The diff may be just deleted columns which we have reset above
                    // Only exec and add output if does really alter anything.
                    // There's a known issue with MySQL, where it will (falsely) notice an updated index,
                    // but those are filtered out here, by the `!empty($msgParts)` bit.
                    // @see: https://github.com/bolt/bolt/issues/3426
                    if ($diffs[] = $this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
                        $msg = 'Table `' . $table->getName() . '` is not the correct schema: ';
                        $msgParts = array();

                        // No check on foreign keys yet because we don't use them
                        /** @var $col Column */
                        foreach ($diff->addedColumns as $col) {
                            $msgParts[] = 'missing column `' . $col->getName() . '`';
                        }
                        /** @var $index Index */
                        foreach ($diff->addedIndexes as $index) {
                            $msgParts[] = 'missing index on `' . implode(', ', $index->getUnquotedColumns()) . '`';
                        }
                        /** @var $col ColumnDiff */
                        foreach ($diff->changedColumns as $col) {
                            $msgParts[] = 'invalid column `' . $col->oldColumnName . '`';
                        }
                        /** @var $index Index */
                        foreach ($diff->changedIndexes as $index) {
                            $msgParts[] = 'invalid index on `' . implode(', ', $index->getUnquotedColumns()) . '`';
                        }
                        foreach ($diff->removedColumns as $colName => $val) {
                            $msgParts[] = 'removed column `' . $colName . '`';
                        }
                        foreach ($diff->removedIndexes as $indexName => $val) {
                            $msgParts[] = 'removed index `' . $indexName . '`';
                        }

                        if (!empty($msgParts)) {
                            $msg .= implode(', ', $msgParts);
                            $messages[] = $msg;
                        }
                    }
                }
            }
        }

        // If there were no messages, update the timer, so we don't check it again.
        // If there _are_ messages, keep checking until it's fixed.
        if (empty($messages)) {
            $this->markValid();
        }

        // If we were passed in a debug logger, log the diffs
        if ($debugLogger !== null) {
            foreach ($diffs as $diff) {
                $debugLogger->info('Database update required', $diff);
            }
        }

        return $hinting ? array($messages, $hints) : $messages;
    }

    /**
     * Determine if we need to check the table integrity. Do this only once per
     * hour, per session, since it's pretty time consuming.
     *
     * @return boolean TRUE if a check is needed
     */
    public function needsCheck()
    {
        return !$this->isValid();
    }

    /**
     * Check and repair tables.
     *
     * @return string[]
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

        $tables = $this->getTablesSchema();

        /** @var $table Table */
        foreach ($tables as $table) {
            // Create the users table.
            if (!isset($currentTables[$table->getName()])) {

                /** @var $platform AbstractPlatform */
                $platform = $this->app['db']->getDatabasePlatform();
                $queries = $platform->getCreateTableSQL($table);
                foreach ($queries as $query) {
                    $this->app['db']->query($query);
                }

                $output[] = 'Created table `' . $table->getName() . '`.';
            } else {
                $diff = $comparator->diffTable($currentTables[$table->getName()], $table);
                if ($diff) {
                    $diff = $this->cleanupTableDiff($diff);
                    // diff may be just deleted columns which we have reset above
                    // only exec and add output if does really alter anything
                    if ($this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
                        $schemaManager->alterTable($diff);
                        $output[] = 'Updated `' . $table->getName() . '` table to match current schema.';
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Cleanup a table diff, remove changes we want to keep or fix platform specific issues.
     *
     * @param TableDiff $diff
     *
     * @return \Doctrine\DBAL\Schema\TableDiff
     */
    protected function cleanupTableDiff(TableDiff $diff)
    {
        $baseTables = $this->getBoltTablesNames();

        // Work around reserved column name removal
        if ($diff->fromTable->getName() == $this->getTablename('cron')) {
            foreach ($diff->renamedColumns as $key => $col) {
                if ($col->getName() == 'interim') {
                    $diff->addedColumns[] = $col;
                    unset($diff->renamedColumns[$key]);
                }
            }
        }

        if (!in_array($diff->fromTable->getName(), $baseTables)) {
            // we don't remove fields from contenttype tables to prevent accidental data removal
            $diff->removedColumns = array();
        }

        return $diff;
    }

    /**
     * Get a merged array of tables.
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getTablesSchema()
    {
        $schema = new Schema();

        return array_merge(
            $this->getBoltTablesSchema($schema),
            $this->getContentTypeTablesSchema($schema),
            $this->getExtensionTablesSchema($schema)
        );
    }

    /**
     * This method allows extensions to register their own tables.
     *
     * @param Callable $generator A generator function that takes the Schema
     *                            instance and returns a table or an array of tables.
     */
    public function registerExtensionTable($generator)
    {
        $this->extension_table_generators[] = $generator;
    }

    /**
     * Get all the registered extension tables.
     *
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getExtensionTablesSchema(Schema $schema)
    {
        $tables = array();
        foreach ($this->extension_table_generators as $generator) {
            $table = call_user_func($generator, $schema);
            // We need to be prepared for generators returning a single table,
            // as well as generators returning an array of tables.
            if (is_array($table)) {
                foreach ($table as $t) {
                    $tables[] = $t;
                }
            } else {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * Get an array of Bolt's internal tables
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getBoltTablesNames()
    {
        $baseTables = array();
        /** @var $table Table */
        foreach ($this->getBoltTablesSchema(new Schema()) as $table) {
            $baseTables[] = $table->getName();
        }

        return $baseTables;
    }

    /**
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getBoltTablesSchema(Schema $schema)
    {
        $tables = array();

        $authtokenTable = $schema->createTable($this->getTablename('authtoken'));
        $authtokenTable->addColumn('id', 'integer', array('autoincrement' => true));
        $authtokenTable->setPrimaryKey(array('id'));
        // TODO: addColumn('userid'...), phase out referencing users by username
        $authtokenTable->addColumn('username', 'string', array('length' => 32, 'default' => ''));
        $authtokenTable->addIndex(array('username'));
        $authtokenTable->addColumn('token', 'string', array('length' => 128));
        $authtokenTable->addColumn('salt', 'string', array('length' => 128));
        $authtokenTable->addColumn('lastseen', 'datetime', array('notnull' => false, 'default' => null));
        $authtokenTable->addColumn('ip', 'string', array('length' => 32, 'default' => ''));
        $authtokenTable->addColumn('useragent', 'string', array('length' => 128, 'default' => ''));
        $authtokenTable->addColumn('validity', 'datetime', array('notnull' => false, 'default' => null));
        $tables[] = $authtokenTable;

        $usersTable = $schema->createTable($this->getTablename('users'));
        $usersTable->addColumn('id', 'integer', array('autoincrement' => true));
        $usersTable->setPrimaryKey(array('id'));
        $usersTable->addColumn('username', 'string', array('length' => 32));
        $usersTable->addUniqueIndex(array('username'));
        $usersTable->addColumn('password', 'string', array('length' => 128));
        $usersTable->addColumn('email', 'string', array('length' => 254));
        $usersTable->addUniqueIndex(array('email'));
        $usersTable->addColumn('lastseen', 'datetime', array('notnull' => false, 'default' => null));
        $usersTable->addColumn('lastip', 'string', array('length' => 32, 'default' => ''));
        $usersTable->addColumn('displayname', 'string', array('length' => 32));
        $usersTable->addColumn('stack', 'string', array('length' => 1024, 'default' => ''));
        $usersTable->addColumn('enabled', 'boolean');
        $usersTable->addIndex(array('enabled'));
        $usersTable->addColumn('shadowpassword', 'string', array('length' => 128, 'default' => ''));
        $usersTable->addColumn('shadowtoken', 'string', array('length' => 128, 'default' => ''));
        $usersTable->addColumn('shadowvalidity', 'datetime', array('notnull' => false, 'default' => null));
        $usersTable->addColumn('failedlogins', 'integer', array('default' => 0));
        $usersTable->addColumn('throttleduntil', 'datetime', array('notnull' => false, 'default' => null));
        $usersTable->addColumn('roles', 'string', array('length' => 1024, 'default' => ''));
        $tables[] = $usersTable;

        $taxonomyTable = $schema->createTable($this->getTablename('taxonomy'));
        $taxonomyTable->addColumn('id', 'integer', array('autoincrement' => true));
        $taxonomyTable->setPrimaryKey(array('id'));
        $taxonomyTable->addColumn('content_id', 'integer');
        $taxonomyTable->addIndex(array('content_id'));
        $taxonomyTable->addColumn('contenttype', 'string', array('length' => 32));
        $taxonomyTable->addIndex(array('contenttype'));
        $taxonomyTable->addColumn('taxonomytype', 'string', array('length' => 32));
        $taxonomyTable->addIndex(array( 'taxonomytype'));
        $taxonomyTable->addColumn('slug', 'string', array('length' => 64));
        $taxonomyTable->addColumn('name', 'string', array('length' => 64, 'default' => ''));
        $taxonomyTable->addColumn('sortorder', 'integer', array('default' => 0));
        $taxonomyTable->addIndex(array( 'sortorder'));
        $tables[] = $taxonomyTable;

        $relationsTable = $schema->createTable($this->getTablename('relations'));
        $relationsTable->addColumn('id', 'integer', array('autoincrement' => true));
        $relationsTable->setPrimaryKey(array('id'));
        $relationsTable->addColumn('from_contenttype', 'string', array('length' => 32));
        $relationsTable->addIndex(array('from_contenttype'));
        $relationsTable->addColumn('from_id', 'integer');
        $relationsTable->addIndex(array('from_id'));
        $relationsTable->addColumn('to_contenttype', 'string', array('length' => 32));
        $relationsTable->addIndex(array('to_contenttype'));
        $relationsTable->addColumn('to_id', 'integer');
        $relationsTable->addIndex(array('to_id'));
        $tables[] = $relationsTable;

        $logSystemTable = $schema->createTable($this->getTablename('log_system'));
        $logSystemTable->addColumn('id', 'integer', array('autoincrement' => true));
        $logSystemTable->setPrimaryKey(array('id'));
        $logSystemTable->addColumn('level', 'integer');
        $logSystemTable->addIndex(array('level'));
        $logSystemTable->addColumn('date', 'datetime');
        $logSystemTable->addIndex(array('date'));
        $logSystemTable->addColumn('message', 'string', array('length' => 1024));
        $logSystemTable->addColumn('ownerid', 'integer', array('notnull' => false));
        $logSystemTable->addIndex(array('ownerid'));
        $logSystemTable->addColumn('requesturi', 'string', array('length' => 128));
        $logSystemTable->addColumn('route', 'string', array('length' => 128));
        $logSystemTable->addColumn('ip', 'string', array('length' => 32, 'default' => ''));
        $logSystemTable->addColumn('context', 'string', array('length' => 32));
        $logSystemTable->addIndex(array( 'context'));
        $logSystemTable->addColumn('source', 'text', array());
        $tables[] = $logSystemTable;

        $logChangeTable = $schema->createTable($this->getTablename('log_change'));
        $logChangeTable->addColumn('id', 'integer', array('autoincrement' => true));
        $logChangeTable->setPrimaryKey(array('id'));
        $logChangeTable->addColumn('date', 'datetime');
        $logChangeTable->addIndex(array('date'));
        $logChangeTable->addColumn('ownerid', 'integer', array('notnull' => false));
        $logChangeTable->addIndex(array('ownerid'));

        // the title as it was right before changing/deleting the item, or
        // right after creating it (according to getTitle())
        $logChangeTable->addColumn('title', 'string', array('length' => 256, 'default' => ''));

        // contenttype and contentid refer to the entity type we're changing
        $logChangeTable->addColumn('contenttype', 'string', array('length' => 128));
        $logChangeTable->addIndex(array('contenttype'));
        $logChangeTable->addColumn('contentid', 'integer', array());
        $logChangeTable->addIndex(array('contentid'));

        // should be one of 'UPDATE', 'INSERT', 'DELETE'
        $logChangeTable->addColumn('mutation_type', 'string', array('length' => 16));
        $logChangeTable->addIndex(array('mutation_type'));

        // a plain-text summary of the differences between the old and the new version
        $logChangeTable->addColumn('diff', 'text', array());

        // message to create a story of revisions
        $logChangeTable->addColumn('comment', 'string', array('length' => 150, 'default' => '', 'notnull' => false));
        $tables[] = $logChangeTable;

        $cronTable = $schema->createTable($this->getTablename('cron'));
        $cronTable->addColumn('id', 'integer', array('autoincrement' => true));
        $cronTable->setPrimaryKey(array('id'));
        $cronTable->addColumn('interim', 'string', array('length' => 16));
        $cronTable->addIndex(array('interim'));
        $cronTable->addColumn('lastrun', 'datetime');
        $tables[] = $cronTable;

        return $tables;
    }

    /**
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getContentTypeTablesSchema(Schema $schema)
    {
        $tables = array();

        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->app['config']->get('contenttypes') as $contenttype) {

            // create the table if necessary.
            $tablename = $this->getTablename($contenttype['tablename']);

            $myTable = $schema->createTable($tablename);
            $myTable->addColumn('id', 'integer', array('autoincrement' => true));
            $myTable->setPrimaryKey(array('id'));
            $myTable->addColumn('slug', 'string', array('length' => 128));
            $myTable->addIndex(array('slug'));
            $myTable->addColumn('datecreated', 'datetime');
            $myTable->addIndex(array('datecreated'));
            $myTable->addColumn('datechanged', 'datetime');
            $myTable->addIndex(array('datechanged'));
            $myTable->addColumn('datepublish', 'datetime', array('notnull' => false, 'default' => null));
            $myTable->addIndex(array('datepublish'));
            $myTable->addColumn('datedepublish', 'datetime', array('notnull' => false, 'default' => null));
            $myTable->addIndex(array('datedepublish'));
            $myTable->addColumn('templatefields', 'text', array('default' => ''));
            $myTable->addColumn('username', 'string', array('length' => 32, 'default' => '', 'notnull' => false)); // We need to keep this around for backward compatibility. For now.
            $myTable->addColumn('ownerid', 'integer', array('notnull' => false));
            $myTable->addColumn('status', 'string', array('length' => 32));
            $myTable->addIndex(array('status'));

            // Check if all the fields are present in the DB.
            foreach ($contenttype['fields'] as $field => $values) {

                /** @var \Doctrine\DBAL\Platforms\Keywords\KeywordList $reservedList */
                $reservedList = $this->app['db']->getDatabasePlatform()->getReservedKeywordsList();
                if ($reservedList->isKeyword($field)) {
                    $error = sprintf(
                        "You're using '%s' as a field name, but that is a reserved word in %s. Please fix it, and refresh this page.",
                        $field,
                        $this->app['db']->getDatabasePlatform()->getName()
                    );
                    $this->app['session']->getFlashBag()->add('error', $error);
                    continue;
                }

                switch ($values['type']) {
                    case 'text':
                    case 'templateselect':
                    case 'file':
                        $myTable->addColumn($field, 'string', array('length' => 256, 'default' => ''));
                        break;
                    case 'float':
                        $myTable->addColumn($field, 'float', array('default' => 0));
                        break;
                    case 'number': // deprecated.
                        $myTable->addColumn($field, 'decimal', array('precision' => '18', 'scale' => '9', 'default' => 0));
                        break;
                    case 'integer':
                        $myTable->addColumn($field, 'integer', array('default' => 0));
                        break;
                    case 'checkbox':
                        $myTable->addColumn($field, 'boolean', array('default' => 0));
                        break;
                    case 'html':
                    case 'textarea':
                    case 'image':
                    case 'video':
                    case 'markdown':
                    case 'geolocation':
                    case 'filelist':
                    case 'imagelist':
                    case 'select':
                        $myTable->addColumn($field, 'text', array('default' => $this->getTextDefault()));
                        break;
                    case 'datetime':
                        $myTable->addColumn($field, 'datetime', array('notnull' => false));
                        break;
                    case 'date':
                        $myTable->addColumn($field, 'date', array('notnull' => false));
                        break;
                    case 'slug':
                        // Only additional slug fields will be added. If it's the
                        // default slug, skip it instead.
                        if ($field != 'slug') {
                            $myTable->addColumn($field, 'string', array('length' => 128, 'notnull' => false, 'default' => ''));
                        }
                        break;
                    case 'id':
                    case 'datecreated':
                    case 'datechanged':
                    case 'datepublish':
                    case 'datedepublish':
                    case 'username':
                    case 'status':
                    case 'ownerid':
                        // These are the default columns. Don't try to add these.
                        break;
                    default:
                        if ($handler = $this->app['config']->getFields()->getField($values['type'])) {
                            /** @var $handler \Bolt\Field\FieldInterface */
                            $myTable->addColumn($field, $handler->getStorageType(), $handler->getStorageOptions());
                        }
                }

                if (isset($values['index']) && $values['index'] == 'true') {
                    $myTable->addIndex(array($field));
                }
            }
            $tables[] = $myTable;
        }

        return $tables;
    }

    /**
     * Get the tablename with prefix from a given $name.
     *
     * @param $name
     *
     * @return string
     */
    protected function getTablename($name)
    {
        $name = str_replace('-', '_', $this->app['slugify']->slugify($name));
        $tablename = sprintf('%s%s', $this->getTablenamePrefix(), $name);

        return $tablename;
    }

    /**
     * Get the tablename prefix
     *
     * @return string
     */
    protected function getTablenamePrefix()
    {
        if ($this->prefix !== null) {
            return $this->prefix;
        }

        $this->prefix = $this->app['config']->get('general/database/prefix', 'bolt_');

        // Make sure prefix ends in '_'. Prefixes without '_' are lame.
        if ($this->prefix[strlen($this->prefix) - 1] != '_') {
            $this->prefix .= '_';
        }

        return $this->prefix;
    }

    /**
     * Default value for TEXT fields, differs per platform.
     *
     * @return string|null
     */
    private function getTextDefault()
    {
        $platform = $this->app['db']->getDatabasePlatform();
        if ($platform instanceof SqlitePlatform || $platform instanceof PostgreSqlPlatform) {
            return '';
        }

        return null;
    }

    /**
     * Get the 'validity' timestamp's file name.
     *
     * @return string
     */
    private function getValidityTimestampFilename()
    {
        if (empty($this->integrityCachePath)) {
            $this->integrityCachePath = $this->app['resources']->getPath('cache');
        }

        return $this->integrityCachePath . '/' . self::INTEGRITY_CHECK_TS_FILENAME;
    }
}
