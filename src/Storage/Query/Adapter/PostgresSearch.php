<?php

namespace Bolt\Storage\Query\Adapter;

use Bolt\Storage\Query\SearchConfig;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 *  Handler to build a fulltext search query for Postgres
 */
class PostgresSearch
{
    
    protected $qb;
    protected $config;
    protected $searchWords;
    
    public function __construct(QueryBuilder $qb, SearchConfig $config, array $searchWords)
    {
        $this->qb = $qb;
        $this->config = $config;
        $this->searchWords = $searchWords;
    }
   
    public function getQuery()
    {
        $words = implode("&", $this->searchWords);
        $this->qb->addSelect("ts_rank(bsearch.document, to_tsquery('".$words."'))", "score");
    }
}
