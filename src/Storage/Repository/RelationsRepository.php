<?php

namespace Bolt\Storage\Repository;

use Bolt\Collection\Bag;
use Bolt\Collection\ImmutableBag;
use Bolt\Storage\Collection;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;

/**
 * A Repository class that handles storage operations for the relations table.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RelationsRepository extends Repository
{
    /**
     * Fetches the related entities for a given ContentType & ID.
     *
     * @param string     $contentTypeKey
     * @param int        $id
     * @param array|null $options
     *
     * @return Collection\Relations
     */
    public function getRelatedEntities($contentTypeKey, $id, $options = null)
    {
        $default = Bag::from([
            'types'      => null,
            'status'     => null,
            'limit'      => null,
            'filter_ids' => null,
        ]);
        $options = $default->merge($options);
        $query = $this->getRelationsQuery($contentTypeKey, $id, $options);
        $base = $this->findWith($query);
        if (!$base) {
            return new Collection\Relations();
        }
        $collection = new Collection\Relations($base);
        $grouped = $collection->getGrouped();
        foreach ($grouped as $type => $relationsEntities) {
            $filters = null;
            foreach ($relationsEntities as $relationsEntity) {
                /** @var Entity\Relations $relationsEntity */
                $filters[] = $relationsEntity->get('to_id');
            }
            $options->set('filter_ids', $filters);
            $grouped[$type] = $this->findRelatedEntities($type, $options);
        }

        return new Collection\Relations($grouped, $this->em);
    }

    public function getRelationsQuery($contentTypeKey, $id, $options)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('from_contenttype = :from_contenttype')
            ->andWhere('from_id = :from_id')
            ->setParameter('from_contenttype', $contentTypeKey)
            ->setParameter('from_id', $id)
        ;
        if ($options['types']) {
            $expr = $qb->expr();
            $or = null;
            foreach ((array) $options['types'] as $type) {
                $or[] = $expr->eq('to_contenttype', ':to_contenttype_' . $type);
                $qb->setParameter('to_contenttype_' . $type, $type);
            }
            $qb->andWhere(call_user_func_array([$expr, 'orX'], $or));
        }

        return $qb;
    }

    /**
     * @param string       $contentTypeKey
     * @param ImmutableBag $options
     *
     * @return Entity\Content[]|null
     */
    private function findRelatedEntities($contentTypeKey, ImmutableBag $options)
    {
        $repo = $this->em->getRepository($contentTypeKey);
        $query = $repo->createQueryBuilder();
        $query->select('*');

        // Status
        if ($options->get('status')) {
            $query->andWhere('status = :status')
                ->setParameter('status', $options->get('status'))
            ;
        }
        // Selected IDs
        if ($options->get('filter_ids')) {
            $expr = $query->expr();
            $or = null;
            foreach ((array) $options->get('filter_ids') as $id) {
                $or[] = $expr->eq('content.id', ':id_' . $id);
                $query->setParameter('id_' . $id, $id);
            }
            $query->andWhere(call_user_func_array([$expr, 'orX'], $or));
        }
        // Maximum number of results
        if ($options->get('limit')) {
            $query->setMaxResults((int) $options->get('limit'));
        }

        return $repo->findWith($query) ?: null;
    }
}
