<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Bolt\Storage\Database\Schema\SchemaCheck;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
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
    /** @var string */
    protected $prefix;
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
     * @param string          $prefix
     * @param LoggerInterface $systemLog
     */
    public function __construct(Connection $connection, $prefix, LoggerInterface $systemLog)
    {
        $this->connection = $connection;
        $this->prefix = $prefix;
        $this->systemLog = $systemLog;
        $this->setIgnoredChanges();
    }

    /**
     * Are database updates required.
     *
     * @param Table[] $fromTables
     * @param Table[] $toTables
     * @param array   $protectedTableNames
     *
     * @return bool
     */
    public function hasPending($fromTables, $toTables, array $protectedTableNames)
    {
        if ($this->pending !== null) {
            return $this->pending;
        }
        $this->compare($fromTables, $toTables, $protectedTableNames);

        return $this->pending;
    }

    /**
     * Run the update checks and flag if we need an update.
     *
     * @param Table[] $fromTables
     * @param Table[] $toTables
     * @param array   $protectedTableNames
     * @param bool    $force
     *
     * @return SchemaCheck
     */
    public function compare($fromTables, $toTables, array $protectedTableNames, $force = false)
    {
        if ($this->response !== null && $force === false) {
            return $this->getResponse();
        }

        $this->checkTables($fromTables, $toTables);

        // If we have diffs, check if they need to be modified
        if ($this->diffs !== null) {
            $this->adjustDiffs($protectedTableNames);
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
     * Get the unmodified table diffs.
     *
     * @return TableDiff[]
     */
    public function getDiffs()
    {
        return $this->diffs;
    }

    /**
     * Add an ignored change to the list.
     *
     * @param IgnoredChange $ignoredChange
     */
    public function addIgnoredChange(IgnoredChange $ignoredChange)
    {
        $this->ignoredChanges[] = $ignoredChange;
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
     *
     * @param Table[] $fromTables
     * @param Table[] $toTables
     */
    protected function checkTables($fromTables, $toTables)
    {
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
            $this->removeIgnoredChanges($diff);
            $this->diffs[$tableName] = $diff;
        }
    }

    /**
     * Platform specific adjustments to table/column diffs.
     *
     * @param array $protectedTableNames
     */
    protected function adjustDiffs(array $protectedTableNames)
    {
        $diffUpdater = new DiffUpdater($this->ignoredChanges);

        /** @var TableDiff $tableDiff */
        foreach ($this->diffs as $tableName => $tableDiff) {
            $this->adjustContentTypeDiffs($tableDiff, $protectedTableNames);
            $this->diffs[$tableName] = $diffUpdater->adjustDiff($tableDiff);
            if ($this->diffs[$tableName] === false) {
                unset($this->diffs[$tableName]);
                continue;
            }
        }
    }

    /**
     * Clear 'removedColumns' attribute from ContentType table diffs to prevent accidental data removal.
     *
     * @param TableDiff $tableDiff
     * @param array     $protectedTableNames
     */
    protected function adjustContentTypeDiffs(TableDiff $tableDiff, array $protectedTableNames)
    {
        $alias = str_replace($this->prefix, '', $tableDiff->fromTable->getName());
        if (in_array($alias, $protectedTableNames)) {
            $tableDiff->removedColumns = [];
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
