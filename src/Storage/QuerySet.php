<?php

namespace Bolt\Storage;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This class works keeps a set of queries that will eventually
 * be executed sequentially.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QuerySet extends \ArrayIterator
{
    protected $resultCallbacks = [];
    protected $lastInsertId;
    protected $parentId;

    /**
     * @param QueryBuilder $qb A QueryBuilder instance
     */
    public function append($qb)
    {
        if (!$qb instanceof QueryBuilder) {
            throw new \InvalidArgumentException('QuerySet will only accept QueryBuilder instances', 1);
        }

        parent::append($qb);
    }

    /**
     * Execute function, iterate the queries, and execute them sequentially.
     *
     * @throws \Exception
     *
     * @return \Doctrine\DBAL\Driver\Statement|int|null
     */
    public function execute()
    {
        $result = null;
        // Only return the result of the primary query
        foreach ($this as $query) {
            /** @var QueryBuilder $query */
            try {
                if ($result === null) {
                    $result = $query->execute();
                    if ($query->getType() === 3) {
                        $this->setLastInsertId($query);
                    }
                    foreach ($this->resultCallbacks as $callback) {
                        $callback($query, $result, $this->getParentId());
                    }
                } else {
                    foreach ($this->resultCallbacks as $callback) {
                        $callback($query, $result, $this->getParentId());
                    }
                    $query->execute();
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Add a callback that gets run before a secondary query that passes the supplementary
     * query and the result of the first query into the callable.
     *
     * @param callable $callback [description]
     */
    public function onResult(callable $callback)
    {
        $this->resultCallbacks[] = $callback;
    }

    /**
     * Getter method to return last insert ID from query.
     *
     * @return int|null
     */
    public function getInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * @TODO Temporary workaround for PostgreSQL databases that don't use a sequence.
     *
     * @param QueryBuilder $query
     */
    private function setLastInsertId(QueryBuilder $query)
    {
        $seq = null;
        if ($query->getConnection()->getDatabasePlatform()->getName() === 'postgresql') {
            $sequences = $query->getConnection()->getSchemaManager()->listSequences();
            $desiredSeq = $query->getQueryPart('from')['table'] . '_id_seq';
            foreach ($sequences as $sequence) {
                if ($desiredSeq === $sequence->getName()) {
                    $seq = $desiredSeq;
                    break;
                }
            }
        }
        $this->lastInsertId = $query->getConnection()->lastInsertId($seq);
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        if (!$this->parentId && $this->lastInsertId) {
            return $this->lastInsertId;
        }

        return $this->parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * A helper method to get the primary database query from a set. Normally this points to the first in the set
     *
     * @return QueryBuilder
     */
    public function getPrimary()
    {
        return $this[0];
    }
}
