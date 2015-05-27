<?php

namespace Bolt\Storage;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This class works keeps a set of queries that will eventually
 * be executed sequentially.
 */
class QuerySet extends \ArrayIterator
{
    /**
     * @param QueryBuilder $qb A QueryBuilder instance
     */
    public function append($qb)
    {
        if (!$qb instanceof QueryBuilder) {
            throw new \InvalidArgumentException("QuerySet will only accept QueryBuilder instances", 1);
        }
        
        parent::append($qb);
    }

    /**
     * Execute function, iterate the queries, and execute them sequentially
     *
     * @return \Doctrine\DBAL\Driver\Statement|int|null
     *
     * @throws \Exception
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
                } else {
                    $query->execute();
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return $result;
    }
}
