<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\Database\Schema\Table\ContentType;
use Doctrine\DBAL\Query\QueryBuilder;
use Pimple;

/**
 *  This query class coordinates a taxonomy query build
 *
 *  The resulting set then generates proxies to various content objects
 *
 *  @author Ross Riley <riley.ross@gmail.com>
 */
class TaxonomyQuery implements QueryInterface
{
    /** @var QueryBuilder */
    protected $qb;
    /** @var array */
    protected $params;
    /** @var array */
    protected $contentTypes;
    /** @var array */
    protected $taxonomyTypes;
    /**@var Pimple */
    private $schema;

    /**
     * Constructor.
     *
     * @param QueryBuilder $qb
     * @param Pimple       $schema
     */
    public function __construct(QueryBuilder $qb, Pimple $schema)
    {
        $this->qb = $qb;
        $this->schema = $schema;
    }

    /**
     * Sets the parameters that will filter / alter the query.
     *
     * @param array $params
     */
    public function setParameters(array $params)
    {
        $this->params = array_filter($params);
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
    }

    /**
     * Setter to specify which content types to search on
     *
     * @param array $contentTypes
     */
    public function setContentTypes(array $contentTypes)
    {
        $this->contentTypes = $contentTypes;
    }

    /**
     * Setter to specify which taxonomy types to search on
     *
     * @param array $taxonomyTypes
     */
    public function setTaxonomyTypes(array $taxonomyTypes)
    {
        $this->taxonomyTypes = $taxonomyTypes;
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
        $this->buildJoin();
        $this->buildWhere();

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
     * @return string String representation of query
     */
    public function __toString()
    {
        $query = $this->build();

        return $query->getSQL();
    }

    protected function buildJoin()
    {
        $subQuery = '(SELECT ';
        $fragments = [];
        foreach ($this->contentTypes as $content) {
            /** @var ContentType  $table */
            $table = $this->schema[$content];
            $tableName = $table->getTableName();
            $fragments[] = "id,status, '$content' AS tablename FROM " . $tableName;
        }
        $subQuery .= join(' UNION SELECT ', $fragments);
        $subQuery .= ')';

        $this->qb->from($subQuery, 'content');
        $this->qb->addSelect('content.*');
    }

    protected function buildWhere()
    {
        $params = [];
        $i = 0;
        $where = $this->qb->expr()->andX();
        foreach ($this->taxonomyTypes as $name => $slug) {
            $where->add($this->qb->expr()->eq('taxonomy.taxonomytype', ':taxonomytype_' . $i));
            $where->add($this->qb->expr()->eq('taxonomy.slug', ':slug_' . $i));
            $params['taxonomytype_' . $i] = $name;
            $params['slug_' . $i] = $slug;
            $i++;
        }
        $this->qb->where($where)->setParameters($params);
        $this->qb->andWhere("content.status='published'");
        $this->qb->andWhere('taxonomy.contenttype=content.tablename');
        $this->qb->andWhere('taxonomy.content_id=content.id');
    }
}
