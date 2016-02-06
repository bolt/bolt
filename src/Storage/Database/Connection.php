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
class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        try {
            return parent::connect();
        } catch (DBALException $e) {
            if ($this->_eventManager->hasListeners('failConnect')) {
                $eventArgs = new FailedConnectionEvent($this, $e);
                $this->_eventManager->dispatchEvent('failConnect', $eventArgs);
            }
        }
    }
}
