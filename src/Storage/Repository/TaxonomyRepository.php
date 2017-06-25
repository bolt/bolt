<?php

namespace Bolt\Storage\Repository;

use Bolt\Storage\Collection;
use Bolt\Storage\Repository;

/**
 * A Repository class that handles storage operations for the taxonomy table.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TaxonomyRepository extends Repository
{
    /**
     * Fetches the taxonomy entities for a given ContentType & ID.
     *
     * @param string            $contentTypeKey
     * @param int               $id
     * @param array|string|null $types
     *
     * @return Collection\Taxonomy
     */
    public function getTaxonomies($contentTypeKey, $id, $types = null)
    {
        $query = $this->getTaxonomiesQuery($contentTypeKey, $id, (array) $types);

        $base = $this->findWith($query);
        if (!$base) {
            return new Collection\Taxonomy();
        }
        $collection = new Collection\Taxonomy($base);
        $grouped = $collection->getGrouped();
        foreach ($grouped as $type => $taxonomyEntities) {
            $grouped[$type] = $taxonomyEntities;
        }

        return new Collection\Taxonomy($grouped);
    }

    public function getTaxonomiesQuery($contentTypeKey, $id, array $types)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('contenttype = :contenttype')
            ->andWhere('content_id = :content_id')
            ->setParameter('contenttype', $contentTypeKey)
            ->setParameter('content_id', $id)
        ;
        if ($types) {
            $expr = $qb->expr();
            $or = null;
            foreach ($types as $type) {
                $or[] = $expr->eq('taxonomytype', ':taxonomytype_' . $type);
                $qb->setParameter('taxonomytype_' . $type, $type);
            }
            $qb->andWhere(call_user_func_array([$expr, 'orX'], $or));
        }

        return $qb;
    }
}
