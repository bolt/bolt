<?php
namespace Bolt\Storage\Field\Type;

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
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RelationType extends FieldTypeBase
{
    /**
     * Relation fields can allow filters on the relations fetched. For now this is limited
     * to the id field because of the possible complexity of fetching and filtering
     * all the related data.
     *
     * For example the following queries:
     *     'pages', {'relationkey'=>'1'}
     *     'pages', {'relationkey'=>'1 || 2 || 3'}.
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
                $method = strtolower($filter->getExpressionObject()->getType()) . 'X';

                $newExpr = $query->getQueryBuilder()->expr()->$method();
                foreach ($filter->getParameters() as $k => $v) {
                    $newExpr->add("$field.to_id = :$k");
                }

                $filter->setExpression($newExpr);
            }
        }
    }

    /**
     * For relations, the load method adds an extra ->addSelect() and ->leftJoin() to the query that
     * fetches the related records from the join table in the same query as the content fetch.
     *
     * IDs are returned comma-separated which the ->hydrate() method can then turn into pointers
     * to the related entities.
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

        $from = $query->getQueryPart('from');

        if (isset($from[0]['alias'])) {
            $alias = $from[0]['alias'];
        } else {
            $alias = $from[0]['table'];
        }

        // Standard relation fetch
        $query
            ->addSelect($this->getPlatformGroupConcat("$field.id", '_' . $field . '_id', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.to_id", '_' . $field . '_toid', $query))
            ->leftJoin($alias, $target, $field, "$alias.id = $field.from_id AND $field.from_contenttype='$boltname' AND $field.to_contenttype='$field'")
            ->addGroupBy("$alias.id");
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        $field = $this->mapping['fieldname'];
        $data = $this->normalizeData($data, $field);
        if (!count($entity->getRelation())) {
            $entity->setRelation($this->em->createCollection('Bolt\Storage\Entity\Relations'));
        }

        $fieldRels = $this->em->createCollection('Bolt\Storage\Entity\Relations');
        foreach ($data as $relData) {
            $rel = [];
            $rel['id'] = $relData['id'];
            $rel['from_id'] = $entity->getId();
            $rel['from_contenttype'] = (string) $entity->getContenttype();
            $rel['to_contenttype'] = $field;
            $rel['to_id'] = $relData['toid'];
            $relEntity = new Entity\Relations($rel);
            $entity->getRelation()->add($relEntity);
            $fieldRels->add($relEntity);
        }
        $this->set($entity, $fieldRels);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $field = $this->mapping['fieldname'];
        $relations = $entity->getRelation()
            ->getField($field);

        // Fetch existing relations and create two sets of records, updates and deletes.
        $existingDB = $this->getExistingRelations($entity) ?: [];
        $existingInverse = $this->getInverseRelations($entity) ?: [];
        $collection = $this->em->createCollection('Bolt\Storage\Entity\Relations');
        $collection->setFromDatabaseValues($existingDB);
        $toDelete = $collection->update($relations);
        $collection->filterInverseValues($existingInverse);
        $repo = $this->em->getRepository('Bolt\Storage\Entity\Relations');

        // Add a listener to the main query save that sets the from ID on save and then saves the relations
        $queries->onResult(
            function ($query, $result, $id) use ($repo, $collection, $toDelete) {
                foreach ($collection as $entity) {
                    $entity->from_id = $id;
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
        return 'relation';
    }

    /**
     * Get existing relationship records.
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function getExistingRelations($entity)
    {
        $query = $this->em->createQueryBuilder()
            ->select('*')
            ->from($this->mapping['target'])
            ->where('from_id = :from_id')
            ->andWhere('from_contenttype = :from_contenttype')
            ->andWhere('to_contenttype = :to_contenttype')
            ->setParameters([
                'from_id'          => $entity->id,
                'from_contenttype' => $entity->getContenttype(),
                'to_contenttype'   => $this->mapping['fieldname'],
            ]);
        $result = $query->execute()->fetchAll();

        return $result ?: [];
    }

    /**
     * Get inverse relationship records. That is ones where the definition happened on the opposite record
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function getInverseRelations($entity)
    {
        $query = $this->em->createQueryBuilder()
            ->select('*')
            ->from($this->mapping['target'])
            ->where('to_id = :to_id')
            ->andWhere('to_contenttype = :to_contenttype')
            ->setParameters([
                'to_id'          => $entity->id,
                'to_contenttype' => $entity->getContenttype(),
            ]);
        $result = $query->execute()->fetchAll();

        return $result ?: [];
    }

    /**
     * Get platform specific group_concat token for provided column
     *
     * @param string       $column
     * @param string       $alias
     * @param QueryBuilder $query
     *
     * @return string
     */
    protected function getPlatformGroupConcat($column, $alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();
        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT($column) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT($column) as $alias";
            case 'postgresql':
                return "string_agg($column" . "::character varying, ',') as $alias";
        }
    }
}
