<?php

namespace Bolt\Storage\Repository;

use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Repository;

/**
 * A Repository class that handles dynamically created content tables.
 */
class ContentRepository extends Repository
{
    
    protected $legacy;
    
    
    public function setLegacyService(ContentLegacyService $service)
    {
        $this->legacy = $service;
        $this->event()->addListener(StorageEvents::POST_HYDRATE, [$this, 'hydrateLegacyHandler']);
    }
    
    
    public function createQueryBuilder($alias = 'content')
    {
        return parent::createQueryBuilder($alias);
    }
    
    public function hydrateLegacyHandler(StorageEvent $event)
    {
        $entity = $event->getContent();
        $entity->setLegacyService($this->legacy);        
    }
}
