<?php

namespace Bolt\Storage;

/**
 *  This class is used by lazily loaded entities. It stores a reference to an entity but only
 *  fetches it on demand.
 */
class EntityProxy
{
    /** @var string */
    public $entity;
    /** @var string */
    public $reference;
    /** @var bool */
    protected $loaded = false;
    /** @var object */
    protected $proxy;

    /** @var EntityManager|null */
    private $em;

    /**
     * Constructor.
     *
     * @param string             $entity    The class name of the object to find.
     * @param string             $reference The identity of the object to find.
     * @param EntityManager|null $em
     */
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

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $this->load();

        return call_user_func_array([$this->proxy, $method], $args);
    }

    /**
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        $this->load();

        return $this->proxy->$attribute;
    }

    /**
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    public function __set($attribute, $value)
    {
        $this->load();

        return $this->proxy->$attribute = $value;
    }
}
