<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\Query\QueryParameterParser;
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
class SelectQuery implements QueryInterface
{
    protected $qb;
    protected $parser;
    protected $contenttype;
    protected $params;
    protected $filters = [];
    protected $replacements = [];

    /**
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb, QueryParameterParser $parser)
    {
        $this->qb = $qb;
        $this->parser = $parser;     
    }
    
    /**
     * Sets the contenttype that this query will run against.
     * 
     * @param string $contenttype
     */
    public function setContentType($contenttype) 
    {
        $this->contenttype = $contenttype;
    }
    
    /**
     * Sets the parameters that will filter / alter the query
     * 
     * @param array $params
     */
    public function setParameters($params)
    {
        $this->params = $params;
        $this->processFilters();
    }

    /**
     * Creates a composite expression that adds all the attached
     * filters individual expressions into a combined one.
     * 
     * @return CompositeExpression 
     */
    public function getWhereExpression()
    {
        $expr = $this->qb->expr()->andX();
        foreach ($this->filters as $filter) {
            $expr = $expr->add($filter->getExpression());
        }

        return $expr;
    }

    /**
     * Returns all the parameters for the query.
     * 
     * @return array
     */
    public function getWhereParameters()
    {
        $params = [];
        foreach ($this->filters as $filter) {
            $params = array_merge($params, $filter->getParameters());
        }

        return $params;
    }
    
    /**
     * Gets all the parameters for a specific field name.
     * 
     * @param  string $fieldname
     * @return array             array of key=>value parameters
     */
    public function getWhereParametersFor($fieldname)
    {
        return array_intersect_key(
            $this->getWhereParameters(), 
            array_flip(preg_grep('/^'.$fieldname.'_/', array_keys($this->getWhereParameters())))
        );   
    }
    
    public function setWhereParameter($key, $val) 
    {
        foreach ($this->filters as $filter) {
            if ($filter->hasParameter($key)) {
                $filter->setParameter($key,$val);
            }
        }
    }

    /**
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }
    
    /**
     * Part of the QueryInterface this turns all the input into a Doctrine
     * QueryBuilder object and is usually run just before query execution.
     * That allows modifications to be made to any of the parameters up until
     * query execution time.
     * 
     * @return QueryBuilder
     */
    public function build()
    {
        $query = $this->qb
            ->where($this->getWhereExpression())
            ->setParameters($this->getWhereParameters());
            
        return $query;
    }
    
    /**
     * Allows public access to the QueryBuilder object
     * 
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }
    
    /**
     * Allows replacing the default querybuilder
     * 
     * @return QueryBuilder
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }
    
    /**
     * Internal method that runs the individual key/value input through
     * the QueryParamtererParser. This allows complicated expressions to
     * be turned into simple sql expressions
     * 
     * @return void
     */
    protected function processFilters()
    {
        foreach ($this->params as $key => $value) {
            $this->parser->setAlias($this->contenttype);
            $this->addFilter($this->parser->getFilter($key, $value));
        }
    }
    

}
