<?php 
namespace Bolt\Storage\Query;

use Bolt\Storage\EntityManager;
use Doctrine\DBAL\Query\QueryBuilder;

/**
*  This query class coordinates a select query build from Bolt's
*  custom query DSL as documented here:
*  @link https://docs.bolt.cm/content-fetching
* 
*  The resulting QueryBuilder object is then passed through to the individual
*  field handlers where they can perform value transformations.
* 
*  @author Ross Riley <riley.ross@gmail.com>
*/
class SelectQuery
{
    
    protected $qb;
    
    protected $filters = [];
    
    /**
     * 
     * @param QueryBuilder $qb
     */
    public function __construct(array $filters, QueryBuilder $qb) {
        $this->qb = $qb;
    }
    
    /**
     * 
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;    
    }
    
    
    
    
    
}