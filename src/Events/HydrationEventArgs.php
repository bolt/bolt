<?php

namespace Bolt\Events;

use Bolt\Storage\Repository;
use Doctrine\Common\EventArgs;

/**
 * Hydration args allow access to 
 * pre and post hydration of entities.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class HydrationEventArgs extends EventArgs
{

    /**
     * @var array
     */
    private $sourceData;

    /**
     * @var object
     */
    private $object;
    
    /**
     * @var Bolt\Storage\Repository
     */
    private $repository;


    /**
     * Constructor.
     *
     * @param object        $object
     * @param ObjectManager $objectManager
     */
    public function __construct($sourceData, $entity, Repository $repository)
    {
        $this->sourceData = $sourceData;
        $this->object = $object;
        $this->repository = $repository;
    }
    
    
    /**
     * @return array
     */
    public function getData()
    {
        return $this->sourceData;
    }
    
    /**
     * @return Object
     */
    public function getObject()
    {
        return $this->object;
    }
    
    public function getRepository()
    {
        return $this->repository;
    }
    
}