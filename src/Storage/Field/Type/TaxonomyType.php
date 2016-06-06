<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Collection;
use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TaxonomyType extends FieldTypeBase
{
    /**
     * Taxonomy fields allows queries on the parameters passed in.
     * For example the following queries:
     *     'pages', {'categories'=>'news'}
     *     'pages', {'categories'=>'news || events'}.
     *
     * Because the search is actually on the join table, we replace the
     * expression to filter the join side rather than on the main side.
     *
     * @param QueryInterface $query
     * @param ClassMetadata  $metadata
     *
     * @return void
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];

        foreach ($query->getFilters() as $filter) {
            if ($filter->getKey() == $field) {
                // This gets the method name, one of andX() / orX() depending on type of expression
                $method = strtolower($filter->getExpressionObject()
                        ->getType()) . 'X';

                $newExpr = $query->getQueryBuilder()
                    ->expr()
                    ->$method();
                foreach ($filter->getParameters() as $k => $v) {
                    $newExpr->add("$field.slug = :$k");
                }

                $filter->setExpression($newExpr);
            }
        }
    }

    /**
     * For the taxonomy field the load event modifies the query to fetch taxonomies related
     * to a content record from the join table.
     *
     * It does this via an additional ->addSelect() and ->leftJoin() call on the QueryBuilder
     * which includes then includes the taxonomies in the same query as the content fetch.
     *
     * @param QueryBuilder  $query
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];
        $target = $this->mapping['target'];
        $boltname = $metadata->getBoltName();

        if ($this->mapping['data']['has_sortorder']) {
            $order = "$field.sortorder";
            $query->addSelect($this->getPlatformGroupConcat("$field.sortorder", $order, '_' . $field . '_sortorder',
                $query));
        } else {
            $order = "$field.id";
        }

        $from = $query->getQueryPart('from');

        if (isset($from[0]['alias'])) {
            $alias = $from[0]['alias'];
        } else {
            $alias = $from[0]['table'];
        }

        $query
            ->addSelect($this->getPlatformGroupConcat("$field.id", $order, '_' . $field . '_id', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.slug", $order, '_' . $field . '_slug', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.name", $order, '_' . $field . '_name', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.taxonomytype", $order, '_' . $field . '_taxonomytype',
                $query))
            ->leftJoin($alias, $target, $field,
                "$alias.id = $field.content_id AND $field.contenttype='$boltname' AND $field.taxonomytype='$field'")
            ->addGroupBy("$alias.id");
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        $taxName = $this->mapping['fieldname'];

        $data = $this->normalizeData($data, $taxName);
        if (!count($entity->getTaxonomy())) {
            $entity->setTaxonomy($this->em->createCollection('Bolt\Storage\Entity\Taxonomy'));
        }

        $fieldTaxonomy = $this->em->createCollection('Bolt\Storage\Entity\Taxonomy');
        foreach ($data as $tax) {
            $tax['content_id'] = $entity->getId();
            $tax['contenttype'] = (string) $entity->getContenttype();
            $taxEntity = new Entity\Taxonomy($tax);
            $entity->getTaxonomy()
                ->add($taxEntity);
            $fieldTaxonomy->add($taxEntity);
        }
        $this->set($entity, $fieldTaxonomy);
        $entity->setGroup($this->getGroup($fieldTaxonomy));
        $entity->setSortorder($this->getSortorder($fieldTaxonomy));
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $field = $this->mapping['fieldname'];
        $taxonomy = $entity->getTaxonomy()
            ->getField($field);

        // Fetch existing taxonomies
        $existingDB = $this->getExistingTaxonomies($entity) ?: [];
        $collection = $this->em->getCollectionManager()
            ->create('Bolt\Storage\Entity\Taxonomy');
        $collection->setFromDatabaseValues($existingDB);
        $toDelete = $collection->update($taxonomy);
        $repo = $this->em->getRepository('Bolt\Storage\Entity\Taxonomy');

        $queries->onResult(
            function ($query, $result, $id) use ($repo, $collection, $toDelete) {
                foreach ($collection as $entity) {
                    $entity->content_id = $id;
                    $repo->save($entity);
                }

                foreach ($toDelete as $entity) {
                    $repo->delete($entity);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'taxonomy';
    }

    /**
     * Get platform specific group_concat token for provided column.
     *
     * @param string       $column
     * @param string       $order
     * @param string       $alias
     * @param QueryBuilder $query
     *
     * @return string
     */
    protected function getPlatformGroupConcat($column, $order, $alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()
            ->getDatabasePlatform()
            ->getName();

        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT($column ORDER BY $order ASC) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT($column) as $alias";
            case 'postgresql':
                return "string_agg($column" . "::character varying, ',' ORDER BY $order) as $alias";
        }
    }

    protected function getGroup(Collection\Taxonomy $taxonomy)
    {
        $group = null;
        $taxData = $this->mapping['data'];
        foreach ($taxonomy as $tax) {
            if ($taxData['has_sortorder']) {
                // Previously we only cared about the last one… so yeah
                $needle = $tax->getSlug();
                $index = array_search($needle, array_keys($taxData['options']));
                $group = [
                    'slug'  => $tax->getSlug(),
                    'name'  => $tax->getName(),
                    'order' => $tax->getSortorder(),
                    'index' => $index ?: 2147483647, // Maximum for a 32-bit integer
                ];
            }
        }

        return $group;
    }

    protected function getSortorder(Collection\Taxonomy $taxonomy)
    {
        $taxData = $this->mapping['data'];
        $sortorder = 0;
        foreach ($taxonomy as $tax) {
            if ($taxData['has_sortorder']) {
                $sortorder = $tax->getSortorder();
            }
        }

        return $sortorder;
    }

    /**
     * Direct query to get existing taxonomy records.
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function getExistingTaxonomies($entity)
    {
        // Fetch existing taxonomies
        $query = $this->em->createQueryBuilder()
            ->select('*')
            ->from($this->mapping['target'])
            ->where('content_id = :content_id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('taxonomytype = :taxonomytype')
            ->setParameters([
                'content_id'   => $entity->id,
                'contenttype'  => $entity->getContenttype(),
                'taxonomytype' => $this->mapping['fieldname'],
            ]);
        $result = $query->execute()
            ->fetchAll();

        return $result ?: [];
    }
}
