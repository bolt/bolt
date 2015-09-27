<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\QuerySet;
use Cocur\Slugify\Slugify;

/**
 * Trait for TaxonomyType logic.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait TaxonomyTypeTrait
{
    /**
     * Get existing taxonomy records.
     *
     * @param mixed $entity
     *
     * @return array
     */
    public function getExisting($entity)
    {
        $field = $this->mapping['fieldname'];
        $target = $this->mapping['target'];

        // Fetch existing taxonomies
        $query = $this->em->createQueryBuilder()
            ->select('*')
            ->from($target)
            ->where('content_id = :content_id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('taxonomytype = :taxonomytype')
            ->setParameters([
                'content_id'   => $entity->id,
                'contenttype'  => $entity->getContenttype(),
                'taxonomytype' => $field,
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
    public function appendInsertQueries(QuerySet $queries, $entity, array $toInsert)
    {
        foreach ($toInsert as $item) {
            $item = (string) $item;
            $ins = $this->em->createQueryBuilder()
                ->insert($this->mapping['target'])
                ->values([
                'content_id'   => '?',
                'contenttype'  => '?',
                'taxonomytype' => '?',
                'slug'         => '?',
                'name'         => '?',
            ])->setParameters([
                0 => $entity->id,
                1 => $entity->getContenttype(),
                2 => $this->mapping['fieldname'],
                3 => Slugify::create()->slugify($item),
                4 => isset($this->mapping['data']['options'][$item]) ? $this->mapping['data']['options'][$item] : $item,
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
    public function appendDeleteQueries(QuerySet $queries, $entity, array $toDelete)
    {
        foreach ($toDelete as $item) {
            $del = $this->em->createQueryBuilder()
                ->delete($this->mapping['target'])
                ->where('content_id=?')
                ->andWhere('contenttype=?')
                ->andWhere('taxonomytype=?')
                ->andWhere('slug=?')
                ->setParameters([
                    0 => $entity->id,
                    1 => $entity->getContenttype(),
                    2 => $this->mapping['fieldname'],
                    3 => $item,
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
