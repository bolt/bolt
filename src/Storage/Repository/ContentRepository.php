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
     * Set the legacy Content service object.
     *
     * @param ContentLegacyService $service
     */
    public function setLegacyService(ContentLegacyService $service)
    {
        $this->legacy = $service;
        $this->event()->addListener(StorageEvents::POST_HYDRATE, [$this, 'hydrateLegacyHandler']);
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
        $entity = $event->getSubject();
        $entity->setLegacyService($this->legacy);
    }
}
