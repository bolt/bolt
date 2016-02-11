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
class Connection extends \Doctrine\DBAL\Connection
{

    protected $_queryCacheProfile;

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

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     *
     * @return array
     */
    public function fetchAll($sql, array $params = array(), $types = array())
    {
        $stmt = $this->executeQuery($sql, $params, $types, $this->_queryCacheProfile);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();

        return $result;
    }

    public function setQueryCacheProfile(QueryCacheProfile $profile)
    {
        $this->_queryCacheProfile = $profile;
    }
}
