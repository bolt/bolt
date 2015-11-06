<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Database\Schema\SchemaCheck;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Pimple;
use Psr\Log\LoggerInterface;

/**
 * Base class for handling table comparison.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class BaseComparator
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;
    /** @var \Bolt\Storage\Database\Schema\Manager */
    protected $manager;
    /** @var \Pimple */
    protected $schemaTables;
    /** @var \Psr\Log\LoggerInterface */
    protected $systemLog;

    /** @var \Doctrine\DBAL\Schema\TableDiff[] */
    protected $diffs;
    /** @var \Doctrine\DBAL\Schema\Table[] */
    protected $tablesCreate;
    /** @var \Doctrine\DBAL\Schema\TableDiff[] */
    protected $tablesAlter;
    /** @var array */
    protected $ignoredChanges = [];
    /** @var boolean */
    protected $pending;
    /** @var \Bolt\Storage\Database\Schema\SchemaCheck */
    protected $response;

    /**
     * Constructor.
     *
     * @param Connection      $connection
     * @param Manager         $manager
     * @param Pimple          $schemaTables
     * @param LoggerInterface $systemLog
     */
    public function __construct(Connection $connection, Manager $manager, Pimple $schemaTables, LoggerInterface $systemLog)
    {
        $this->connection = $connection;
        $this->manager = $manager;
        $this->schemaTables = $schemaTables;
        $this->systemLog = $systemLog;
    }

    /**
     * Are database updates required.
     *
     * @return boolean
     */
    public function hasPending()
    {
        if ($this->pending !== null) {
            return $this->pending;
        }
        $this->compare();

        return $this->pending;
    }

    /**
     * Run the update checks and flag if we need an update.
     *
     * @param boolean $force
     *
     * @return SchemaCheck
     */
    public function compare($force = false)
    {
        if ($this->response !== null && $force === false) {
            return $this->getResponse();
        }

        $this->checkTables();

        // If we have diffs, check if they need to be modified
        if ($this->diffs !== null) {
            $this->adjustDiffs();
            $this->addAlterResponses();
        }

        return $this->getResponse();
    }

    /**
     * Get the schema check response object.
     *
     * @return \Bolt\Storage\Database\Schema\SchemaCheck
     */
    public function getResponse()
    {
        if ($this->response !== null) {
            return $this->response;
        }
        return $this->response = new SchemaCheck();
    }

    /**
     * Get the table creation SQL queries.
     *
     * @return string[]
     */
    public function getCreates()
    {
        if ($this->tablesCreate === null) {
            return [];
        }

        $queries = [];
        foreach ($this->tablesCreate as $tableName => $table) {
            $queries[$tableName] = $this->connection->getDatabasePlatform()
                ->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS);
        }

        return $queries;
    }

    /**
     * Get the table alteration SQL queries.
     *
     * @return string[]
     */
    public function getAlters()
    {
        $queries = [];
        if ($this->tablesAlter === null) {
            return $queries;
        }

        /** @var $tableDiff TableDiff */
        foreach ($this->tablesAlter as $tableName => $tableDiff) {
            $queries[$tableName] = $this->connection->getDatabasePlatform()
                ->getAlterTableSQL($tableDiff);
        }

        return $queries;
    }

    /**
     * Remove table/column diffs that for verious reasons aren't supported on a
     * platform.
     *
     * @param TableDiff $diff
     */
    abstract protected function removeIgnoredChanges(TableDiff $diff);
}
