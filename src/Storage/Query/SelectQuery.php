<?php

namespace Bolt\Storage\Query;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 *  This query class coordinates a select query build from Bolt's
 *  custom query DSL as documented here:.
 *
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
    protected $contenttype;
    protected $params;
    protected $filters = [];

    /**
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb, $contenttype, array $params = null)
    {
        $this->qb = $qb;
        $this->contenttype = $contenttype;
        $this->params = $params;
        $this->processFilters();
    }

    public function processFilters()
    {
        foreach ($this->params as $key => $value) {
            $parser = new QueryParameterParser($key, $value, $this->qb);
            $this->addFilter($parser->getFilter());
        }
    }

    public function getWhereExpression()
    {
        $expr = $this->qb->expr()->andX();
        foreach ($this->filters as $filter) {
            $expr = $expr->add($filter->getExpression());
        }

        return $expr;
    }

    public function getWhereParameters()
    {
        $params = [];
        foreach ($this->filters as $filter) {
            $params = array_merge($params, $filter->getParameters());
        }

        return $params;
    }

    /**
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }
    
    public function build()
    {
        $query = $this->qb
            ->where($this->getWhereExpression())
            ->setParameters($this->getWhereParameters());
            
        return $query;
    }

}
