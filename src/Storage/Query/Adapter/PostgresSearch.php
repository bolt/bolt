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
    protected $contenttype;

    public function __construct(QueryBuilder $qb, SearchConfig $config, array $searchWords)
    {
        $this->qb = $qb;
        $this->config = $config;
        $this->searchWords = $searchWords;
    }

    public function setContentType($type)
    {
        $this->contenttype = $type;
    }

    public function getQuery()
    {
        $words = implode('&', $this->searchWords);
        $sub = clone $this->qb;
        $this->qb->addSelect("ts_rank(bsearch.document, to_tsquery('".$words."')) as score");
        $sub->select('*');
        $select = [];

        $fieldsToSearch = $this->config->getConfig($this->contenttype);
        $joins = $this->config->getJoins($this->contenttype);
        $fieldsToSearch = array_diff_key($fieldsToSearch, array_flip($joins));

        $from = $this->qb->getQueryPart('from');
        if (isset($from[0]['alias'])) {
            $alias = $from[0]['alias'];
        } else {
            $alias = $from[0]['table'];
        }
        foreach ($fieldsToSearch as $fieldName => $config) {
            $weight = $this->getWeight($config['weight']);
            $select[] = "setweight(to_tsvector($alias.$fieldName), '$weight')";
        }
        $sub->select('*, '.implode(' || ', $select). ' AS document');
        $sub->groupBy("$alias.id");

        $this->qb->from('('.$sub->getSQL().')', 'bsearch');

        $this->qb->where("bsearch.document @@ to_tsquery('".$words."')");
        $this->qb->orderBy('score', 'DESC');
        return $this->qb;
    }

    public function getWeight($score)
    {
        switch (true) {
            case ($score >= 75):
                return 'A';

            case ($score >= 50):
                return 'B';

            case ($score >= 25):
                return 'C';

            case ($score < 25):
                return 'D';
        }
        return 'A';
    }
}
