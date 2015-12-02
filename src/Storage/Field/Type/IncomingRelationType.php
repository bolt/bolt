<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class IncomingRelationType extends RelationType
{
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

        $query
            ->addSelect($this->getPlatformGroupConcat("$field.id", '_' . $field . '_id', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.from_id", '_' . $field . '_fromid', $query))
            ->addSelect($this->getPlatformGroupConcat("$field.from_contenttype", '_' . $field . '_fromcontenttype', $query))
            ->leftJoin($alias, $target, $field, "$alias.id = $field.to_id AND $field.to_contenttype='$boltname'")
            ->addGroupBy("$alias.id");
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        $field = $this->mapping['fieldname'];
        $data = $this->normalizeData($data, $field);

        if (!$entity->getRelation()) {
            $entity->setRelation($this->em->createCollection('Bolt\Storage\Entity\Relations'));
        }

        foreach ($data as $relData) {
            $rel = [];
            $rel['id'] = $relData['id'];
            $rel['from_id'] = $relData['fromid'];
            $rel['from_contenttype'] = $relData['fromcontenttype'];
            $rel['to_contenttype'] = (string) $entity->getContenttype();
            $rel['to_id'] = $entity->getId();
            $relEntity = new Entity\Relations($rel);
            $entity->getRelation()->add($relEntity);
        }
    }

    public function persist(QuerySet $queries, $entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'incomingrelation';
    }
}
