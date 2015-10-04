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
     * Get an associative array of ['slug' => 'name'] for taxonomy values.
     *
     * @param string $taxName
     * @param array  $data
     *
     * @return array
     */
    protected function getTaxonomyValues($taxName, array $data)
    {
        $taxonomy = [];
        $slugs = explode(',', $data[$taxName . '_slugs']);
        $names = explode(',', $data[$taxName]);
        foreach ($slugs as $index => $value) {
            $taxonomy[$value] = $names[$index];
        }

        return $taxonomy;
    }

    /**
     * Get existing taxonomy records.
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
            $item = (string) $item;
            $ins = $this->em->createQueryBuilder()
                ->insert($this->mapping['target'])
                ->values([
                    'content_id'   => ':content_id',
                    'contenttype'  => ':contenttype',
                    'taxonomytype' => ':taxonomytype',
                    'slug'         => ':slug',
                    'name'         => ':name',
                ])->setParameters([
                    'content_id'   => $entity->id,
                    'contenttype'  => $entity->getContenttype(),
                    'taxonomytype' => $this->mapping['fieldname'],
                    'slug'         => Slugify::create()->slugify($item),
                    'name'         => isset($this->mapping['data']['options'][$item]) ? $this->mapping['data']['options'][$item] : $item,
                ]);

            $queries->onResult(function ($query, $result, $id) use ($ins) {
                if ($query === $ins && $result === 1 && $id) {
                    $query->setParameter('content_id', $id);
                }
            });

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
                ->where('content_id = :content_id')
                ->andWhere('contenttype = :contenttype')
                ->andWhere('taxonomytype = :taxonomytype')
                ->andWhere('slug = :slug')
                ->setParameters([
                    'content_id'   => $entity->id,
                    'contenttype'  => $entity->getContenttype(),
                    'taxonomytype' => $this->mapping['fieldname'],
                    'slug'         => $item,
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
