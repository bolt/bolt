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
}
