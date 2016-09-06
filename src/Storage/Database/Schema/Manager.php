<?php

namespace Bolt\Storage\Database\Schema;

use Bolt\Events\SchemaEvent;
use Bolt\Events\SchemaEvents;
use Bolt\Storage\Database\Schema\Table\BaseTable;
use Doctrine\DBAL\Schema\Schema;
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

    /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
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
     * @deprecated Deprecated since 3.0, to be removed in 4.0. This is a place holder to prevent fatal errors.
     *
     * @param string $name
     * @param mixed  $args
     */
    public function __call($name, $args)
    {
        $this->app['logger.system']->warning('[DEPRECATED]: An extension called an invalid, or removed, integrity checker function: ' . $name, ['event' => 'deprecated']);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. This is a place holder to prevent fatal errors.
     *
     * @param string $name
     */
    public function __get($name)
    {
        $this->app['logger.system']->warning('[DEPRECATED]: An extension called an invalid, or removed integrity, checker property: ' . $name, ['event' => 'deprecated']);
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
        $tableName = null;
        if (isset($this->app['schema.tables'][$name])) {
            /** @var BaseTable $table */
            $table = $this->app['schema.tables'][$name];
            $tableName = $table->getTableName();
        }

        return $tableName;
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
        $fromTables = $this->getInstalledTables();
        $toTables = $this->getSchemaTables();
        $pending = $this->getSchemaComparator()->hasPending($fromTables, $toTables, $this->app['schema.content_tables']->keys());

        if (!$pending) {
            $this->getSchemaTimer()->setCheckExpiry();
        }

        return $pending;
    }

    /**
     * Run a check against current and configured schemas.
     *
     * @return SchemaCheck
     */
    public function check()
    {
        $fromTables = $this->getInstalledTables();
        $toTables = $this->getSchemaTables();
        $response = $this->getSchemaComparator()->compare($fromTables, $toTables, $this->app['schema.content_tables']->keys());
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
        $fromTables = $this->getInstalledTables();
        $toTables = $this->getSchemaTables();
        $this->getSchemaComparator()->compare($fromTables, $toTables, $this->app['schema.content_tables']->keys());
        $response = $this->getSchemaComparator()->getResponse();
        $creates = $this->getSchemaComparator()->getCreates();
        $alters = $this->getSchemaComparator()->getAlters();

        $modifier = new TableModifier($this->connection, $this->app['logger.system'], $this->app['logger.flash']);
        $modifier->createTables($creates, $response);
        $modifier->alterTables($alters, $response);

        $event = new SchemaEvent($creates, $alters);
        $this->app['dispatcher']->dispatch(SchemaEvents::UPDATE, $event);

        // Recheck now that we've processed
        $fromTables = $this->getInstalledTables();
        $toTables = $this->getSchemaTables();
        $this->getSchemaComparator()->compare($fromTables, $toTables, $this->app['schema.content_tables']->keys());
        if (!$this->getSchemaComparator()->hasPending($fromTables, $toTables, $this->app['schema.content_tables']->keys())) {
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

        /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
        $this->app['schema.builder']['extensions']->addPrefix($this->app['schema.prefix']);

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
