<?php

namespace Bolt\Search;


/**
 * Description of DoctineDBALQueryBuilderAdapter
 *
 * @author leon
 */
class DoctineDBALQueryBuilderAdapter implements QueryBuilderAdapterInterface
{
    protected $queryBuilder;
    
    public function __construct(\Doctrine\DBAL\Query\QueryBuilder $queryBuilder) 
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function modifyQuery($ids) 
    {
        $this->queryBuilder->where("id IN (" . implode(",", $ids) . ")");
    }
}
