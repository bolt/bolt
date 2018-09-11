<?php

namespace Bolt\Storage\Repository;

use Bolt\Events\QueryEvent;
use Bolt\Events\QueryEvents;
use Bolt\Exception\InvalidRepositoryException;
use Bolt\Storage\Query\TaxonomyQueryResultset;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

class TaxonomyRepository extends Repository
{
    /**
     * @param array $contentTypes
     * @param array $taxonomyTypes
     *
     * @throws InvalidRepositoryException
     *
     * @return QueryBuilder
     */
    public function queryContentByTaxonomy($contentTypes, $taxonomyTypes)
    {
        $query = $this->createQueryBuilder();
        $this->buildJoin($query, $contentTypes);
        $this->buildWhere($query, $taxonomyTypes);

        return $query;
    }

    /**
     * @param QueryBuilder $query
     *
     * @return TaxonomyQueryResultset
     */
    public function getContentByTaxonomy(QueryBuilder $query)
    {
        $results = $query->execute()->fetchAll();
        $set = new TaxonomyQueryResultset();
        $set->setEntityManager($this->getEntityManager());
        $set->add($results);
        $set->setOriginalQuery('getcontent', $query);
        $executeEvent = new QueryEvent($query, $set);
        $this->getEntityManager()->getEventManager()->dispatch(QueryEvents::EXECUTE, $executeEvent);

        return $set;
    }

    /**
     * @param QueryBuilder $query
     * @param array        $contentTypes
     *
     * @throws InvalidRepositoryException
     */
    protected function buildJoin(QueryBuilder $query, $contentTypes)
    {
        $subQuery = '(SELECT ';
        $fragments = [];
        foreach ($contentTypes as $content) {
            $repo = $this->getEntityManager()->getRepository($content);
            $table = $repo->getTableName();
            $fragments[] = "id,status, '$content' AS tablename FROM " . $table;
        }
        $subQuery .= join(' UNION SELECT ', $fragments);
        $subQuery .= ')';

        $query->from($subQuery, 'content');
        $query->addSelect('content.*');
    }

    /**
     * @param QueryBuilder $query
     * @param array        $taxonomyTypes
     */
    protected function buildWhere(QueryBuilder $query, $taxonomyTypes)
    {
        $params = [];
        $i = 0;
        $where = $query->expr()->andX();
        foreach ($taxonomyTypes as $name => $slug) {
            $where->add($query->expr()->eq('taxonomy.taxonomytype', ':taxonomytype_' . $i));
            $where->add($query->expr()->eq('taxonomy.slug', ':slug_' . $i));
            $params['taxonomytype_' . $i] = $name;
            $params['slug_' . $i] = $slug;
            $i++;
        }
        $query->where($where)->setParameters($params);
        $query->andWhere("content.status='published'");
        $query->andWhere('taxonomy.contenttype=content.tablename');
        $query->andWhere('taxonomy.content_id=content.id');
    }
}
