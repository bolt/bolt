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
class MasterSlaveConnection extends \Doctrine\DBAL\Connections\MasterSlaveConnection
{
    /**
     * {@inheritDoc}
     */
    public function connect($connectionName = null)
    {
        try {
            return parent::connect($connectionName);
        } catch (DBALException $e) {
            if ($this->_eventManager->hasListeners(Events::postConnect)) {
                $eventArgs = new ConnectionEventArgs($this);
                $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
            }
        }
    }
}
