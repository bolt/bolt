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
    /** @var QueryCacheProfile */
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

            return false;
        }
    }

    /**
     * This method wraps the native fetchAll method to pass in the configured QueryCacheProfile.
     * If the profile is set to null then operation will continue identically to the standard, otherwise
     * the existence of a cache profile will result in the executeQueryCache() method being called.
     *
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     *
     * @return array
     */
    public function fetchAll($sql, array $params = [], $types = [])
    {
        $stmt = $this->executeQuery($sql, $params, $types, $this->_queryCacheProfile);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();

        return $result;
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $query  The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The parameter types.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return integer The number of affected rows.
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        $result = parent::executeUpdate($query, $params, $types);
        $this->_queryCacheProfile->getResultCacheDriver()->flushAll();

        return $result;
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
