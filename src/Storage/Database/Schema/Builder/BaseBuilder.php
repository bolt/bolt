<?php

namespace Bolt\Storage\Database\Schema\Builder;

use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Database\Schema\Manager;
use Doctrine\DBAL\Connection;
use Pimple;
use Psr\Log\LoggerInterface;

/**
 * Base class for Bolt's table builders.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class BaseBuilder
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;
    /** @var \Bolt\Storage\Database\Schema\Manager */
    protected $manager;
    /** @var \Pimple */
    protected $tables;
    /** @var string */
    protected $prefix;
    /** @var \Psr\Log\LoggerInterface */
    protected $systemLog;
    /** @var \Bolt\Logger\FlashLoggerInterface */
    protected $flashLogger;

    /**
     * Constructor.
     *
     * @param Connection           $connection
     * @param Manager              $manager
     * @param Pimple               $tables
     * @param string               $prefix
     * @param LoggerInterface      $systemLog
     * @param FlashLoggerInterface $flashLogger
     */
    public function __construct(Connection $connection, Manager $manager, Pimple $tables, $prefix, LoggerInterface $systemLog, FlashLoggerInterface $flashLogger)
    {
        $this->connection = $connection;
        $this->manager = $manager;
        $this->tables = $tables;
        $this->prefix = $prefix;
        $this->systemLog = $systemLog;
        $this->flashLogger = $flashLogger;
    }
}
