<?php

namespace Bolt\Events;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Event\ConnectionEventArgs;

/**
 * Event dispatched on Doctrine ConnectionException occurrence.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FailedConnectionEvent extends ConnectionEventArgs
{
    /** @var \Doctrine\DBAL\DBALException */
    private $exception;

    /**
     * Constructor.
     *
     * @param \Doctrine\DBAL\Connection    $connection
     * @param \Doctrine\DBAL\DBALException $exception
     */
    public function __construct(Connection $connection, DBALException $exception)
    {
        parent::__construct($connection);
        $this->exception = $exception;
    }

    /**
     * Getter for the exception.
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public function getException()
    {
        return $this->exception;
    }
}
