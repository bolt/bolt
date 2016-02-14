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
    protected $charset;
    /** @var string */
    protected $collate;
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
     * @param string               $charset
     * @param string               $collate
     * @param LoggerInterface      $systemLog
     * @param FlashLoggerInterface $flashLogger
     */
    public function __construct(Connection $connection, Manager $manager, Pimple $tables, $charset, $collate, LoggerInterface $systemLog, FlashLoggerInterface $flashLogger)
    {
        $this->connection = $connection;
        $this->manager = $manager;
        $this->tables = $tables;
        $this->charset = $charset;
        $this->collate = $collate;
        $this->systemLog = $systemLog;
        $this->flashLogger = $flashLogger;
    }
}
