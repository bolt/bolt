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
     *  @param A QueryBuilder instance
     *
     *  @return void
     */
    public function append($qb)
    {
        if (!$qb instanceof QueryBuilder) {
            throw new \InvalidArgumentException("QuerySet will only accept QueryBuilder instances", 1);
        }
        
        parent::append($qb);
    }
    
    
    /**
     * Execute function, interates the queries and executes them sequentially
     *
     * @return void
     *
     * @author
     **/
    public function execute()
    {
        $result = null;
        // Only return the result of the primary query
        foreach ($this as $query) {
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
