<?php

namespace Bolt\Storage\Query;

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
    public function __construct(QueryBuilder $qb, QueryParameterParser $parser, array $config)
    {
        parent::__construct($qb, $parser);
        $this->config = $config;
    }
    
    public function setSearch($search)
    {
        $this->search = $search;
    }
}