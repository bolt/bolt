<?php

namespace Bolt\Storage\Query;

use Bolt\Exception\QueryParseException;
use Bolt\Storage\Query\SearchConfig;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This query class coordinates a search query building mainly on the same 
 * filtering system used in the SelectQuery class. The main difference is
 * the addition of weighting, which is driven by documented here:.
 *
 *  @link https://docs.bolt.cm/content-search
 *
 *  The resulting QueryBuilder object is then passed through to the individual
 *  field handlers where they can perform value transformations.
 *
 *  @author Ross Riley <riley.ross@gmail.com>
 */
class SearchQuery extends SelectQuery implements QueryInterface
{
    
    protected $search;
    
    /**
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb, QueryParameterParser $parser, SearchConfig $config)
    {
        parent::__construct($qb, $parser);
        $this->config = $config;
    }
    
    public function setSearch($search)
    {
        $this->search = $search;
        $this->processFilters();
    }
    
    public function setParameters($params)
    {
        $this->params = $params;
    }
    
    public function getSearchWords()
    {
        return explode(' ', $this->search);
    }
    
    protected function getSearchParameter()
    {
        if (strpos($this->search, "+")) {
            $words = preg_split('/[\s\+]+/', $this->search);
            return '%'.implode("% && %", $words).'%'; 
        } else {
            $words = explode(" ", $this->search);
            return '%'.implode("% || %", $words).'%';
        }
    }
    
    protected function processFilters()
    {
        if (!$this->contenttype) {
            throw new QueryParseException("You have attempted to run a search query without specifying a contenttype", 1);
        }

        if (!$config = $this->config->getConfig($this->contenttype)) {
            throw new QueryParseException("You have attempted to run a search query on an unknown contenttype", 1);
        }
                
        $params = $this->params;
        unset($params['filter']);

        foreach($config as $field => $options) {
            if ($field === 'taxonomy') {
                foreach($options as $taxonomy => $values) {
                    $params[$taxonomy] = $this->getSearchParameter();
                }
            } else {
                $params[$field] = $this->getSearchParameter();
            }
            
        }
        
        $this->params = $params;

        parent::processFilters();
    }
    
    /**
     * Creates a composite expression that adds all the attached
     * filters individual expressions into a combined one.
     *
     * @return CompositeExpression
     */
    public function getWhereExpression()
    {
        if (!count($this->filters)) {
            return null;
        }

        $expr = $this->qb->expr()->orX();
        foreach ($this->filters as $filter) {
            $expr = $expr->add($filter->getExpression());
        }

        return $expr;
    }
    
}