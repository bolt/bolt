<?php

namespace Bolt\Storage\Query;

use Bolt\Exception\QueryParseException;
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

    /**
     * This method sets the search filter which then triggers the process method.
     *
     * @param string $search full search query
     */
    public function setSearch($search)
    {
        $this->search = $search;
        $this->processFilters();
    }

    /**
     * Sets the overall parameters on the query. This may include others
     * than the search query itself which gets set to the 'filter' param.
     *
     * @param [type] $params [description]
     */
    public function setParameters($params)
    {
        $this->params = $params;
    }

    /**
     * Gets the individual elements of the search query as an array
     *
     * @return array
     */
    public function getSearchWords()
    {
        return explode(' ', $this->search);
    }

    /**
     * This is an internal helper method to get the search words prepared to
     * be passed to the expression builder.
     *
     * @return string
     */
    protected function getSearchParameter()
    {
        if (strpos($this->search, '+')) {
            $words = preg_split('/[\s\+]+/', $this->search);

            return '%'.implode('% && %', $words).'%';
        } else {
            $words = explode(' ', $this->search);

            return '%'.implode('% || %', $words).'%';
        }
    }

    /**
     * This overrides the SelectQuery default to do some extra preparation for a search query.
     * Firstly it builds separate filters for the search query and then it removes the filter
     * from the params and the others will then get processed normally by the parent.
     */
    protected function processFilters()
    {
        if (!$this->contenttype) {
            throw new QueryParseException('You have attempted to run a search query without specifying a contenttype', 1);
        }

        if (!$config = $this->config->getConfig($this->contenttype)) {
            throw new QueryParseException('You have attempted to run a search query on an unknown contenttype or one that is not searchable', 1);
        }

        $params = $this->params;
        unset($params['filter']);

        foreach ($config as $field => $options) {
            $params[$field] = $this->getSearchParameter();
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
            return;
        }

        $expr = $this->qb->expr()->orX();
        foreach ($this->filters as $filter) {
            $expr = $expr->add($filter->getExpression());
        }

        return $expr;
    }
}
