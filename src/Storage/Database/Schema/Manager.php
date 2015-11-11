<?php

namespace Bolt\Storage\Database\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Silex\Application;

/**
 * Manager class for Bolt database schema.
 *
 * Based on on parts of the monolithic Bolt\Database\IntegrityChecker class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;
    /** @var \Bolt\Config */
    protected $config;
    /** @var \Doctrine\DBAL\Schema\Schema */
    protected $schema;
    /** @var \Doctrine\DBAL\Schema\Table[] */
    protected $schemaTables;
    /** @var \Doctrine\DBAL\Schema\Table[] */
    protected $installedTables;

    /** @var \Silex\Application */
    private $app;

    /** @deprecated Will be removed in v3 */
    const INTEGRITY_CHECK_INTERVAL    = 1800; // max. validity of a database integrity check, in seconds
    const INTEGRITY_CHECK_TS_FILENAME = 'dbcheck_ts'; // filename for the check timestamp file

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->connection = $app['db'];
        $this->config = $app['config'];
    }

    /**
     * @deprecated Will be removed in v3. This is a place holder to prevent fatal errors.
     */
    public function __call($name, $args)
    {
        $this->app['logger.system']->warning('[DEPRECATED]: An extension is called an invalid or removed integrity checker function: ' . $name, ['event' => 'deprecated']);
    }

    /**
     * @deprecated Will be removed in v3. This is a place holder to prevent fatal errors.
     */
    public function __get($name)
    {
        $this->app['logger.system']->warning('[DEPRECATED]: An extension is called an invalid or removed integrity checker property: ' . $name, ['event' => 'deprecated']);
    }

    /**
     * Get the database name of a table from an alias.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getTableName($name)
    {
        if (isset($this->app['schema.tables'][$name])) {
            return $this->app['schema.tables'][$name]->getTableName();
        }
    }

    /**
     * Check to see if we have past the time to re-check our schema.
     *
     * @return boolean
     */
    public function isCheckRequired()
    {
        return $this->getSchemaTimer()->isCheckRequired();
    }

    /**
     * Check to see if there is a mismatch in installed versus configured
     * schemas.
     *
     * @return boolean
     */
    public function isUpdateRequired()
    {
        return $this->getSchemaComparator()->hasPending();
    }

    /**
     * Run a check against current and configured schemas.
     *
     * @return SchemaCheck
     */
    public function check()
    {
        $response = $this->getSchemaComparator()->compare();
        if (!$response->hasResponses()) {
            $this->getSchemaTimer()->setCheckExpiry();
        }

        return $response;
    }

    /**
     * Run database table updates.
     *
     * @return \Bolt\Storage\Database\Schema\SchemaCheck
     */
    public function update()
    {
        // Do the initial check
        $this->getSchemaComparator()->compare();
        $response = $this->getSchemaComparator()->getResponse();
        $creates = $this->getSchemaComparator()->getCreates();
        $alters = $this->getSchemaComparator()->getAlters();

        $modifier = new TableModifier($this->connection, $this->app['logger.system'], $this->app['logger.flash']);
        $modifier->createTables($creates, $response);
        $modifier->alterTables($alters, $response);

        // Recheck now that we've processed
        $this->getSchemaComparator()->compare();
        if (!$this->getSchemaComparator()->hasPending()) {
            $this->getSchemaTimer()->setCheckExpiry();
        }

        return $response;
    }

    /**
     * Check if just the users table is present.
     *
     * @return boolean
     */
    public function hasUserTable()
    {
        $tables = $this->getInstalledTables();
        if (isset($tables['users'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the built schema.
     *
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function getSchema()
    {
        if ($this->schema === null) {
            $this->getSchemaTables();
        }

        return $this->schema;
    }

    /**
     * Get a merged array of tables.
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getSchemaTables()
    {
        if ($this->schemaTables !== null) {
            return $this->schemaTables;
        }

        $schema = new Schema();
        $tables = array_merge(
            $this->app['schema.builder']['base']->getSchemaTables($schema),
            $this->app['schema.builder']['content']->getSchemaTables($schema, $this->config),
            $this->app['schema.builder']['extensions']->getSchemaTables($schema)
        );
        $this->schema = $schema;

        return $tables;
    }

    /**
     * Get the installed table list from Doctrine.
     *
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    public function getInstalledTables()
    {
        if ($this->installedTables !== null) {
            return $this->installedTables;
        }

        /** @var $tables \Doctrine\DBAL\Schema\Table[] */
        $tables = $this->connection->getSchemaManager()->listTables();
        foreach ($tables as $table) {
            $alias = str_replace($this->app['schema.prefix'], '', $table->getName());
            $this->installedTables[$alias] = $table;
        }

        return $this->installedTables;
    }

    /**
     * This method allows extensions to register their own tables.
     *
     * @param callable $generator A generator function that takes the Schema
     *                            instance and returns a table or an array of
     *                            tables.
     */
    public function registerExtensionTable(callable $generator)
    {
        $this->app['schema.builder']['extensions']->addTable($generator);
    }

    /**
     * @return \Bolt\Storage\Database\Schema\Timer
     */
    private function getSchemaTimer()
    {
        return $this->app['schema.timer'];
    }

    /**
     * @return \Bolt\Storage\Database\Schema\Comparison\BaseComparator
     */
    private function getSchemaComparator()
    {
        return $this->app['schema.comparator'];
    }
}
