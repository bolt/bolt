<?php

namespace Bolt\Storage\Database;

use Bolt\Events\FailedConnectionEvent;
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
     * {@inheritdoc}
     */
    public function connect($connectionName = null)
    {
        try {
            return parent::connect($connectionName);
        } catch (DBALException $e) {
            if ($this->_eventManager->hasListeners('failConnect')) {
                $eventArgs = new FailedConnectionEvent($this, $e);
                $this->_eventManager->dispatchEvent('failConnect', $eventArgs);
            }
        }
    }
}
