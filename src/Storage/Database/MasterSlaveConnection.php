<?php

namespace Bolt\Storage\Database;

use Bolt\Events\FailedConnectionEvent;
use Doctrine\DBAL\Cache\QueryCacheProfile;
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
    /** @var QueryCacheProfile */
    protected $_queryCacheProfile;

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

            return false;
        }
    }

    /**
     * Sets an optional Query Cache handler on the connection class
     *
     * @param QueryCacheProfile $profile
     */
    public function setQueryCacheProfile(QueryCacheProfile $profile)
    {
        $this->_queryCacheProfile = $profile;
    }
}
