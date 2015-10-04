<?php
namespace Bolt\Storage\Database\Schema;

use Bolt\Storage\Database\Schema\Table\BaseTable;
use Bolt\Storage\Database\Schema\Table\ContentType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class Manager
{
    /** @var \Silex\Application */
    private $app;
    /** @var string */
    private $prefix;
    /** @var \Doctrine\DBAL\Schema\Table[] Current tables. */
    private $tables;

    /** @var array Array of callables that produce table definitions. */
    protected $extension_table_generators = [];
    /** @var string */
    protected $integrityCachePath;

    const INTEGRITY_CHECK_INTERVAL    = 1800; // max. validity of a database integrity check, in seconds
    const INTEGRITY_CHECK_TS_FILENAME = 'dbcheck_ts'; // filename for the check timestamp file

    public $tableMap = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
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

        $this->tables = [];

        foreach ($sm->listTables() as $table) {
            $this->tables[$table->getName()] = $table;
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
     * @param boolean $hinting Return hints if true
     *
     * @return CheckResponse
     */
    public function checkTablesIntegrity($hinting = false)
    {
        $response = new CheckResponse($hinting);
        $tables = $this->getTablesSchema();
        $valid = true;

        /** @var $table Table */
        foreach ($tables as $table) {
            // Set the valid flag via bitwise
            $valid = $valid & $this->checkTableIntegrity($table, $response);
        }

        // If there were no messages, update the timer, so we don't check it again.
        // If there _are_ messages, keep checking until it's fixed.
        if ($valid) {
            $this->markValid();
        }

        return $response;
    }

    /**
     * Check that a single table's columns and indices are present in the DB.
     *
     * @param Table         $table
     * @param CheckResponse $response
     *
     * @return boolean
     */
    protected function checkTableIntegrity(Table $table, CheckResponse $response)
    {
        $comparator = new Comparator();
        $currentTables = $this->getTableObjects();
        $tableName = $table->getName();

        // Create the users table.
        if (!isset($currentTables[$tableName])) {
            $response->addTitle($tableName, sprintf('Table `%s` is not present.', $tableName));
        } else {
            $diff = $comparator->diffTable($currentTables[$tableName], $table);
            $this->addResponseDiff($tableName, $diff, $response);
        }

        // If we are using the debug logger, log the diffs
        foreach ($response->getDiffDetails() as $diff) {
            $this->app['logger.system']->debug('Database update required', $diff);
        }

        // If a table still has messages return a false to flick the validity check
        return !$response->hasResponses();
    }

    /**
     * Add details of the table differences to the response object.
     *
     * @param string          $tableName
     * @param TableDiff|false $diff
     * @param CheckResponse   $response
     */
    protected function addResponseDiff($tableName, $diff, CheckResponse $response)
    {
        if ($diff === false) {
            return;
        }

        $diff = $this->cleanupTableDiff($diff);
        if ($details = $this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
            $response->addTitle($tableName, sprintf('Table `%s` is not the correct schema:', $tableName));
            $response->checkDiff($tableName, $diff);

            // For debugging we keep the diffs
            $response->addDiffDetail($details);
        }
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
     * Check if there are pending updates to the tables.
     *
     * @return boolean
     */
    public function needsUpdate()
    {
        $response = $this->checkTablesIntegrity();

        return $response->hasResponses() ? true : false;
    }

    /**
     * Check and repair tables.
     *
     * @return CheckResponse
     */
    public function repairTables()
    {
        // When repairing tables we want to start with an empty flashbag. Otherwise we get another
        // 'repair your DB'-notice, right after we're done repairing.
        $this->app['logger.flash']->clear();

        $response = new CheckResponse();
        $currentTables = $this->getTableObjects();
        /** @var $schemaManager AbstractSchemaManager */
        $schemaManager = $this->app['db']->getSchemaManager();
        $comparator = new Comparator();
        $tables = $this->getTablesSchema();

        /** @var $table Table */
        foreach ($tables as $table) {
            $tableName = $table->getName();

            // Create the users table.
            if (!isset($currentTables[$tableName])) {

                /** @var $platform AbstractPlatform */
                $platform = $this->app['db']->getDatabasePlatform();
                $queries = $platform->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS);
                foreach ($queries as $query) {
                    $this->app['db']->query($query);
                }

                $response->addTitle($tableName, sprintf('Created table `%s`.', $tableName));
            } else {
                $diff = $comparator->diffTable($currentTables[$tableName], $table);
                if ($diff) {
                    $diff = $this->cleanupTableDiff($diff);
                    // diff may be just deleted columns which we have reset above
                    // only exec and add output if does really alter anything
                    if ($this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
                        $schemaManager->alterTable($diff);
                        $response->addTitle($tableName, sprintf('Updated `%s` table to match current schema.', $tableName));
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Cleanup a table diff, remove changes we want to keep or fix platform
     * specific issues.
     *
     * @param TableDiff $diff
     *
     * @return \Doctrine\DBAL\Schema\TableDiff
     */
    protected function cleanupTableDiff(TableDiff $diff)
    {
        $baseTables = $this->getBoltTablesNames();

        // Work around reserved column name removal
        if ($diff->fromTable->getName() === $this->getTablename('cron')) {
            foreach ($diff->renamedColumns as $key => $col) {
                if ($col->getName() === 'interim') {
                    $diff->addedColumns[] = $col;
                    unset($diff->renamedColumns[$key]);
                }
            }
        }

        // Some diff changes can be ignored… Because… DBAL.
        $alias = $this->getTableAlias($diff->fromTable->getName());
        if (isset($this->app['schema.tables'][$alias]) && $ignored = $this->app['schema.tables'][$alias]->ignoredChanges()) {
            $this->removeIgnoredChanges($this->app['schema.tables'][$alias], $diff, $ignored);
        }

        // Don't remove fields from contenttype tables to prevent accidental data removal
        if (!in_array($diff->fromTable->getName(), $baseTables)) {
            $diff->removedColumns = [];
        }

        return $diff;
    }

    /**
     * Woraround for the json_array types on SQLite. If only the type has
     * changed, we ignore to prevent multiple schema warnings.
     *
     * @param BaseTable $boltTable
     * @param TableDiff $diff
     * @param array     $ignored
     */
    protected function removeIgnoredChanges(BaseTable $boltTable, TableDiff $diff, array $ignored)
    {
        if ($diff->fromTable->getName() !== $boltTable->getTableName()) {
            return;
        }

        foreach ($ignored as $ignore) {
            if (isset($diff->changedColumns[$ignore['column']])
                && $diff->changedColumns[$ignore['column']]->changedProperties === [$ignore['property']]) {
                unset($diff->changedColumns[$ignore['column']]);
            }
        }
    }

    /**
     * Get a merged array of tables.
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getTablesSchema()
    {
        $schema = new Schema();

        $tables = array_merge(
             $this->getBoltTablesSchema($schema),
             $this->getContentTypeTablesSchema($schema),
             $this->getExtensionTablesSchema($schema)
         );

        foreach ($tables as $index => $table) {
            if (strpos($table->getName(), $this->getTablenamePrefix()) === false) {
                unset($tables[$index]);
            }
        }

        return $tables;
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
        $tables = [];
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
        $baseTables = [];
        /** @var $table Table */
        foreach ($this->getBoltTablesSchema(new Schema()) as $table) {
            $baseTables[] = $table->getName();
        }

        return $baseTables;
    }

    /**
     * Build the schema for base Bolt tables.
     *
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getBoltTablesSchema(Schema $schema)
    {
        $tables = [];
        foreach ($this->app['schema.base_tables']->keys() as $name) {
            $tables[] = $this->app['schema.base_tables'][$name]->buildTable($schema, $this->getTablename($name));
        }

        return $tables;
    }

    /**
     * Build the schema for Bolt ContentType tables.
     *
     * @param Schema $schema
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getContentTypeTablesSchema(Schema $schema)
    {
        $tables = [];

        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->app['config']->get('contenttypes') as $contenttype) {
            $tableObj = $this->getContentTypeTableObject($contenttype['tablename']);
            $tableName = $this->getTablename($contenttype['tablename']);
            $this->mapTableName($tableName, $contenttype['tablename']);
            $myTable = $tableObj->buildTable($schema, $tableName);

            if (isset($contenttype['fields']) && is_array($contenttype['fields'])) {
                $this->addContentTypeTableColumns($tableObj, $myTable, $contenttype['fields']);
            }

            $tables[] = $myTable;
        }

        return $tables;
    }

    /**
     * Ensure any late added ContentTypes have a valid table object in the provider.
     *
     * @param string $contenttype
     *
     * @return \Bolt\Storage\Database\Schema\Table\ContentType
     */
    private function getContentTypeTableObject($contenttype)
    {
        if (!isset($this->app['schema.tables'][$contenttype])) {
            $platform = $this->app['db']->getDatabasePlatform();
            $this->app['schema.tables'][$contenttype] = $this->app->share(function () use ($platform) {
                return new ContentType($platform);
            });
        }

        return $this->app['schema.tables'][$contenttype];
    }

    /**
     * Add the custom columns for the ContentType.
     *
     * @param \Bolt\Storage\Database\Schema\Table\ContentType $tableObj
     * @param \Doctrine\DBAL\Schema\Table                     $table
     * @param array                                           $fields
     */
    private function addContentTypeTableColumns(ContentType $tableObj, Table $table, array $fields)
    {
        // Check if all the fields are present in the DB.
        foreach ($fields as $fieldName => $values) {
            /** @var \Doctrine\DBAL\Platforms\Keywords\KeywordList $reservedList */
            $reservedList = $this->app['db']->getDatabasePlatform()->getReservedKeywordsList();
            if ($reservedList->isKeyword($fieldName)) {
                $error = sprintf(
                    "You're using '%s' as a field name, but that is a reserved word in %s. Please fix it, and refresh this page.",
                    $fieldName,
                    $this->app['db']->getDatabasePlatform()->getName()
                );
                $this->app['logger.flash']->error($error);
                continue;
            }

            $this->addContentTypeTableColumn($tableObj, $table, $fieldName, $values);
        }
    }

    /**
     * Add a single column to the ContentType table.
     *
     * @param \Bolt\Storage\Database\Schema\Table\ContentType $tableObj
     * @param \Doctrine\DBAL\Schema\Table                     $table
     * @param string                                          $fieldName
     * @param array                                           $values
     */
    private function addContentTypeTableColumn(ContentType $tableObj, Table $table, $fieldName, array $values)
    {
        if ($tableObj->isKnownType($values['type'])) {
            // Use loose comparison on true as 'true' in YAML is a string
            $addIndex = isset($values['index']) && $values['index'] == 'true';
            // Add the contenttype's specific fields
            $tableObj->addCustomFields($fieldName, $this->getContentTypeTableColumnType($values), $addIndex);
        } elseif ($handler = $this->app['config']->getFields()->getField($values['type'])) {
            // Add template fields
            /** @var $handler \Bolt\Storage\Field\FieldInterface */
            $table->addColumn($fieldName, $handler->getStorageType(), $handler->getStorageOptions());
        }
    }

    /**
     * Certain field types can have single or JSON array types, figure it out.
     *
     * @param array $values
     *
     * @return string
     */
    private function getContentTypeTableColumnType(array $values)
    {
        // Multi-value selects are stored as JSON arrays
        if (isset($values['type']) && $values['type'] === 'select' && isset($values['multiple']) && $values['multiple'] === 'true') {
            return 'selectmultiple';
        }

        return $values['type'];
    }

    /**
     * Get the tablename with prefix from a given $name.
     *
     * @param $name
     *
     * @return string
     */
    public function getTablename($name)
    {
        $name = str_replace('-', '_', $this->app['slugify']->slugify($name));
        $tablename = sprintf('%s%s', $this->getTablenamePrefix(), $name);

        return $tablename;
    }

    /**
     * Get the table alias name.
     *
     * @param $name
     *
     * @return string
     */
    protected function getTableAlias($tableName)
    {
        return str_replace($this->getTablenamePrefix(), '', $tableName);
    }

    /**
     * Get the tablename prefix.
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

    /**
     * Map a table name's value.
     *
     * @param string $from
     * @param string $to
     */
    protected function mapTableName($from, $to)
    {
        $this->tableMap[$from] = $to;
    }

    /**
     * Get the stored table name key.
     *
     * @param string $table
     *
     * @return string
     */
    public function getKeyForTable($table)
    {
        if (isset($this->tableMap[$table])) {
            return $this->tableMap[$table];
        }
    }
}
