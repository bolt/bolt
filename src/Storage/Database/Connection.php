<?php

namespace Bolt\Storage\Database;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\DBALException;

/**
 * Extension of DBAL's Connection class to allow catching of database connection
 * exceptions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        try {
            return parent::connect();
        } catch (DBALException $e) {
            if ($this->_eventManager->hasListeners('failConnect')) {
                $eventArgs = new ConnectionEventArgs($this);
                $this->_eventManager->dispatchEvent('failConnect', $eventArgs);
            }
        }
    }
}
