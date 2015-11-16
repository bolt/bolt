<?php

namespace Bolt\Storage\Database\Schema;

use Bolt\Logger\FlashLoggerInterface;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;

/**
 * Table modification handler class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TableModifier
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;
    /** @var \Psr\Log\LoggerInterface */
    protected $loggerSystem;
    /** @var \Bolt\Logger\FlashLoggerInterface */
    protected $loggerFlash;

    /**
     * Constructor.
     *
     * @param Connection           $connection
     * @param LoggerInterface      $loggerSystem
     * @param FlashLoggerInterface $loggerFlash
     */
    public function __construct(Connection $connection, LoggerInterface $loggerSystem, FlashLoggerInterface $loggerFlash)
    {
        $this->connection = $connection;
        $this->loggerSystem = $loggerSystem;
        $this->loggerFlash = $loggerFlash;
    }

    /**
     * Process a group of tables create queries.
     *
     * @param array       $tableCreates
     * @param SchemaCheck $response
     */
    public function createTables(array $tableCreates, SchemaCheck $response)
    {
        foreach ($tableCreates as $tableName => $tableCreate) {
            $this->createTable($tableName, $tableCreate, $response);
        }
    }

    /**
     * Process a group of tables alter queries.
     *
     * @param array       $tableAlters
     * @param SchemaCheck $response
     */
    public function alterTables(array $tableAlters, SchemaCheck $response)
    {
        foreach ($tableAlters as $tableName => $tableAlter) {
            $this->alterTable($tableName, $tableAlter, $response);
        }
    }

    /**
     * Process a single table create query.
     *
     * @param string      $tableName
     * @param array       $tableCreate
     * @param SchemaCheck $response
     */
    protected function createTable($tableName, array $tableCreate, SchemaCheck $response)
    {
        foreach ($tableCreate as $query) {
            if ($this->runQuery($tableName, $query)) {
                $response->addTitle($tableName, sprintf('Created table `%s`.', $tableName));
            }
        }
    }

    /**
     * Process a single table alter queries.
     *
     * @param string      $tableName
     * @param array       $tableAlter
     * @param SchemaCheck $response
     */
    protected function alterTable($tableName, array $tableAlter, SchemaCheck $response)
    {
        foreach ($tableAlter as $query) {
            if ($this->runQuery($tableName, $query) === null) {
                // Don't continue processing a table after exception is hit.
                return;
            }
            $response->addTitle($tableName, sprintf('Updated `%s` table to match current schema.', $tableName));
        }
    }

    /**
     * Run the create/alter query.
     *
     * @param string $tableName
     * @param string $query
     *
     * @return \Doctrine\DBAL\Driver\Statement|null
     */
    protected function runQuery($tableName, $query)
    {
        try {
            return $this->connection->query($query);
        } catch (DBALException $e) {
            $this->loggerSystem->critical($e->getMessage(), ['event' => 'exception', 'exception' => $e]);
            $this->loggerFlash->error(
                Trans::__('An error occured while updating `%TABLE%`. The error is %ERROR%',
                ['%TABLE%' => $tableName, '%ERROR%' => $e->getMessage()]
            ));
        }
    }
}
