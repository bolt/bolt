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
    /** @var IgnoredChange[] */
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
        $this->setIgnoredChanges();
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
     * Create a list of changes this platform will ignore.
     */
    abstract protected function setIgnoredChanges();

    /**
     * Remove table/column diffs that for verious reasons aren't supported on a
     * platform.
     *
     * @param TableDiff $diff
     */
    abstract protected function removeIgnoredChanges(TableDiff $diff);

    /**
     * Run the checks on the tables to see if they firstly exist, then if they
     * require update.
     */
    protected function checkTables()
    {
        $fromTables = $this->manager->getInstalledTables();
        $toTables = $this->manager->getSchemaTables();

        /** @var $fromTable Table */
        foreach ($toTables as $toTableAlias => $toTable) {
            $tableName = $toTable->getName();

            if (!isset($fromTables[$toTableAlias])) {
                // Table doesn't exist. Mark it for pending creation.
                $this->pending = true;
                $this->tablesCreate[$tableName] = $toTable;
                $this->getResponse()->addTitle($tableName, sprintf('Table `%s` is not present.', $tableName));
                $this->systemLog->debug('Database table missing: ' . $tableName);
                continue;
            }

            // Table exists. Check for required updates.
            $fromTable = $fromTables[$toTableAlias];
            $this->checkTable($fromTable, $toTable);
        }
    }

    /**
     * Check that a single table's columns and indices are valid.
     *
     * @param Table $fromTable
     * @param Table $toTable
     */
    protected function checkTable(Table $fromTable, Table $toTable)
    {
        $tableName = $fromTable->getName();
        $diff = (new Comparator())->diffTable($fromTable, $toTable);
        if ($diff !== false) {
            $this->diffs[$tableName] = $diff;
        }
    }

    /**
     * Platform specific adjustments to table/column diffs.
     */
    protected function adjustDiffs()
    {
        $diffUpdater = new DiffUpdater($this->ignoredChanges);

        /** @var $diff TableDiff */
        foreach ($this->diffs as $tableName => $tableDiff) {
            $this->diffs[$tableName] = $diffUpdater->adjustDiff($tableDiff);
            if ($this->diffs[$tableName] === false) {
                unset($this->diffs[$tableName]);
                continue;
            }
        }
    }

    /**
     * Add required changes to the response object.
     *
     * NOTE: This must be run after adjustDiffs() so that the user response
     * doesn't contain ignored changes.
     */
    protected function addAlterResponses()
    {
        foreach ($this->diffs as $tableName => $tableDiff) {
            $this->pending = true;
            $this->tablesAlter[$tableName] = $tableDiff;
            $this->getResponse()->addTitle($tableName, sprintf('Table `%s` is not the correct schema:', $tableName));
            $this->getResponse()->checkDiff($tableName, $tableDiff);
            $this->systemLog->debug('Database update required', $this->connection->getDatabasePlatform()->getAlterTableSQL($tableDiff));
        }
    }
}
