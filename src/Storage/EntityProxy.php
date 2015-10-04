<?php

namespace Bolt\Storage;

/**
 *  This class is used by lazily loaded entities. It stores a reference to an entity but only
 *  fetches it on demand.
 */
class EntityProxy
{
    public $entity;
    public $reference;
    protected $loaded = false;
    protected $proxy;
    private $em;

    public function __construct($entity, $reference, EntityManager $em = null)
    {
        $this->entity = $entity;
        $this->reference = $reference;
        $this->em = $em;
    }

    public function load()
    {
        if ($this->loaded) {
            return true;
        }
        $this->proxy = $this->em->find($this->entity, $this->reference);
        $this->loaded = true;
        $this->em = null;
    }

    public function __call($method, $args)
    {
        $this->load();
        return call_user_func_array(array($this->proxy, $method), $args);
    }

    public function __get($attribute)
    {
        $this->load();
        return $this->proxy->$attribute;
    }

    public function __set($attribute, $value)
    {
        $this->load();
        return $this->proxy->$attribute = $value;
    }
}
