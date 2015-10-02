<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\QuerySet;

/**
 * Trait for RelationType logic.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait RelationTypeTrait
{
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
     * Append record inserts to the query.
     *
     * @param QuerySet $queries
     * @param mixed    $entity
     * @param array    $toInsert
     */
    protected function appendInsertQueries(QuerySet $queries, $entity, array $toInsert)
    {
        foreach ($toInsert as $item) {
            $ins = $this->em->createQueryBuilder()
                ->insert($this->mapping['target'])
                ->values([
                    'from_id'          => ':from_id',
                    'from_contenttype' => ':from_contenttype',
                    'to_contenttype'   => ':to_contenttype',
                    'to_id'            => ':to_id'
                ])
                ->setParameters([
                    'from_id'          => $entity->id,
                    'from_contenttype' => $entity->getContenttype(),
                    'to_contenttype'   => $this->mapping['fieldname'],
                    'to_id'            => $item
                ]);

            $queries->append($ins);
        }
    }

    /**
     * Append record deletes to the query.
     *
     * @param QuerySet $queries
     * @param mixed    $entity
     * @param array    $toDelete
     */
    protected function appendDeleteQueries(QuerySet $queries, $entity, array $toDelete)
    {
        foreach ($toDelete as $item) {
            $del = $this->em->createQueryBuilder()
                ->delete($this->mapping['target'])
                ->where('from_id = :from_id')
                ->andWhere('from_contenttype = :from_contenttype')
                ->andWhere('to_contenttype = :to_contenttype')
                ->andWhere('to_id = :to_id')
                ->setParameters([
                    'from_id'          => $entity->id,
                    'from_contenttype' => $entity->getContenttype(),
                    'to_contenttype'   => $this->mapping['fieldname'],
                    'to_id'            => $item
                ]);

            $queries->append($del);
        }
    }

    /**
     * Filter empty attributes from an array.
     *
     * @param array $arr
     *
     * @return array
     */
    protected function filterArray(array $arr)
    {
        foreach ($arr as $key => $value) {
            if (empty($value)) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }
}
