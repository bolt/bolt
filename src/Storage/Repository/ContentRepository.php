<?php

namespace Bolt\Storage\Repository;

use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Repository;

/**
 * A Repository class that handles dynamically created content tables.
 */
class ContentRepository extends Repository
{
    /** @var ContentLegacyService */
    protected $legacy;

    /**
     * Fetches details on records for select lists.
     *
     * @param string $contentType
     * @param string $order
     *
     * @return \Bolt\Storage\Entity\Cron
     */
    public function getSelectList($contentType, $order)
    {
        $query = $this->querySelectList($contentType, $order);

        return $query->execute()->fetchAll();
    }

    /**
     * Build the query for a record select list.
     *
     * @param string $contentType
     * @param string $order
     *
     * @return QueryBuilder
     */
    public function querySelectList($contentType, $order)
    {
        if (strpos($order, '-') === 0) {
            $direction = 'ASC';
            $order = ltrim($order, '-');
        } else {
            $direction = 'DESC';
        }

        $qb = $this->createQueryBuilder($contentType);
        $qb->select('id, ' . $this->getTitleColumnName() . ' as title')
            ->orderBy($order, $direction)
        ;

        return $qb;
    }

    /**
     * Set the legacy Content service object.
     *
     * @param ContentLegacyService $service
     */
    public function setLegacyService(ContentLegacyService $service)
    {
        $this->legacy = $service;
        $this->event()->addListener(StorageEvents::PRE_HYDRATE, [$this, 'hydrateLegacyHandler']);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($alias = 'content')
    {
        return parent::createQueryBuilder($alias);
    }

    /**
     * Hydration handler for the legacy object.
     *
     * @param HydrationEvent $event
     */
    public function hydrateLegacyHandler(HydrationEvent $event)
    {
        $entity = $event->getArgument('entity');
        $entity->setLegacyService($this->legacy);
    }

    /**
     * Get the likely column name of the title.
     *
     * @return array
     */
    protected function getTitleColumnName()
    {
        $names = [
            'title', 'name', 'caption', 'subject', // EN
            'titel', 'naam', 'onderwerp',          // NL
            'nom', 'sujet',                        // FR
            'nombre', 'sujeto'                     // ES
        ];

        $columns = $this->getEntityManager()
            ->getConnection()
            ->getSchemaManager()
            ->listTableColumns($this->getTableName())
        ;

        foreach ($names as $name) {
            if (isset($columns[$name])) {
                return $name;
            }
        }
    }
}
