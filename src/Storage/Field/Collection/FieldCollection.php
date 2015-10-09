<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Storage\Entity\FieldValue;
use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 *  This class is used by lazily loaded field values. It stores a reference to an array of rows and
 *  fetches from the database on demand.

 *  @author Ross Riley <riley.ross@gmail.com>
 */
class FieldCollection extends AbstractLazyCollection
{
    public $references = [];
    protected $em;
    protected $grouping;
    protected $toRemove = [];

    /**
     * @param array $references
     * @param EntityManager|null $em
     */
    public function __construct(array $references = [], EntityManager $em = null)
    {
        $this->references = $references;
        $this->em = $em;
    }

    /**
     * @return array
     */
    public function getNew()
    {
        $created = [];
        foreach ($this as $k => $entity) {
            $id = $entity->getId();
            if (!$id) {
                $created[] = $entity;
            }
        }

        return $created;
    }

    /**
     * @return array
     */
    public function getExisting()
    {
        $set = [];
        foreach ($this as $k => $entity) {
            $id = $entity->getId();
            if ($id) {
                $set[] = $entity;
            }
        }

        return $set;
    }

    /**
     * @param mixed $grouping
     */
    public function setGrouping($grouping)
    {
        $this->grouping = $grouping;
    }

    /**
     * @param mixed $element
     * @return bool
     */
    public function add($element)
    {
        $element->setGrouping($this->grouping);
        return parent::add($element);
    }

    /**
     * Handles the conversion of references to entities.
     */
    protected function doInitialize()
    {
        $objects = [];
        if ($this->references) {
            $repo = $this->em->getRepository('Bolt\Storage\Entity\FieldValue');
            $instances = $repo->findBy(['id' => $this->references]);


            foreach ((array)$instances as $val) {
                $objects[$val->getName()] = $val;
            }
        }

        $this->collection = new ArrayCollection($objects);
        $this->em = null;
    }

}
