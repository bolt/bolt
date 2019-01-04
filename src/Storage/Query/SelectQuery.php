<?php

namespace Bolt\Storage\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 *  This query class coordinates a select query build from Bolt's
 *  custom query DSL as documented here:.
 *
 *  @see https://docs.bolt.cm/templates/content-fetching
 *
 *  The resulting QueryBuilder object is then passed through to the individual
 *  field handlers where they can perform value transformations.
 *
 *  @author Ross Riley <riley.ross@gmail.com>
 */
class SelectQuery implements ContentQueryInterface
{
    /** @var QueryBuilder */
    protected $qb;
    /** @var QueryParameterParser */
    protected $parser;
    /** @var string */
    protected $contentType;
    /** @var array */
    protected $params;
    /** @var Filter[] */
    protected $filters = [];
    protected $replacements = [];
    /** @var bool */
    protected $singleFetchMode = false;

    /**
     * Constructor.
     *
     * @param QueryBuilder         $qb
     * @param QueryParameterParser $parser
     */
    public function __construct(QueryBuilder $qb, QueryParameterParser $parser)
    {
        $this->qb = $qb;
        $this->parser = $parser;
    }

    /**
     * Sets the ContentType that this query will run against.
     *
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Gets the ContentType that this query will run against.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Sets the parameters that will filter / alter the query.
     *
     * @param array $params
     */
    public function setParameters(array $params)
    {
        $this->params = array_filter($params);
        $this->processFilters();
    }

    /**
     * Getter to allow access to a set parameter.
     *
     * @param $name
     *
     * @return array|null
     */
    public function getParameter($name)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return null;
    }

    /**
     * Setter to allow writing to a named parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value)
    {
        $this->params[$name] = $value;
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
        if (!count($this->filters)) {
            return null;
        }

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
     * @param string $fieldName
     *
     * @return array array of key=>value parameters
     */
    public function getWhereParametersFor($fieldName)
    {
        return array_intersect_key(
            $this->getWhereParameters(),
            array_flip(preg_grep('/^' . $fieldName . '_\d+$/', array_keys($this->getWhereParameters())))
        );
    }

    /**
     * Sets all the parameters for a specific field name.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setWhereParameter($key, $value)
    {
        foreach ($this->filters as $filter) {
            if ($filter->hasParameter($key)) {
                $filter->setParameter($key, $value);
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
     * Returns all the filters attached to the query.
     *
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
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
        $query = $this->qb;
        if ($this->getWhereExpression()) {
            $query->where($this->getWhereExpression());
        }
        foreach ($this->getWhereParameters() as $key => $param) {
            $query->setParameter($key, $param, is_array($param) ? Connection::PARAM_STR_ARRAY : null);
        }

        return $query;
    }

    /**
     * Allows public access to the QueryBuilder object.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * Allows replacing the default QueryBuilder.
     *
     * @param QueryBuilder $qb
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * Returns whether the query is in single fetch mode.
     *
     * @return bool
     */
    public function getSingleFetchMode()
    {
        return $this->singleFetchMode;
    }

    /**
     * Turns single fetch mode on or off.
     *
     * @param bool $value
     */
    public function setSingleFetchMode($value)
    {
        $this->singleFetchMode = (bool) $value;
    }

    /**
     * @return string String representation of query
     */
    public function __toString()
    {
        $query = $this->build();

        return $query->getSQL();
    }

    /**
     * Internal method that runs the individual key/value input through
     * the QueryParameterParser. This allows complicated expressions to
     * be turned into simple sql expressions.
     *
     * @throws \Bolt\Exception\QueryParseException
     */
    protected function processFilters()
    {
        $this->filters = [];
        foreach ($this->params as $key => $value) {
            $this->parser->setAlias('_' . $this->contentType);
            $filter = $this->parser->getFilter($key, $value);
            if ($filter) {
                $this->addFilter($filter);
            }
        }
    }
}
