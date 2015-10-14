<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Storage\Entity\FieldValue;
use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Fields
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RepeatingFieldCollection extends ArrayCollection
{
    protected $em;
    protected $name;

    /**
     * RepeatingFieldCollection constructor.
     *
     * @param EntityManager $em
     * @param array         $elements
     */
    public function __construct(EntityManager $em, $elements = [])
    {
        $this->em = $em;
        parent::__construct($elements);
    }

    /**
     * @param FieldCollection $collection
     *
     * @return bool
     */
    public function add($collection)
    {
        return parent::add($collection);
    }

    /**
     * @param array $fields
     * @param int   $grouping
     */
    public function addFromArray(array $fields, $grouping = 0)
    {
        $collection = new FieldCollection([], $this->em);
        $collection->setGrouping($grouping);
        foreach ($fields as $name => $value) {
            $field = new FieldValue();
            $field->setName($this->getName());
            $field->setValue($value);
            $field->setFieldname($name);
            $field->setGrouping($grouping);
            $collection->add($field);
        }

        $this->add($collection);
    }

    /**
     * @param array $ids
     * @param int   $grouping
     */
    public function addFromReferences(array $ids, $grouping = 0)
    {
        $collection = new FieldCollection($ids, $this->em);
        $collection->setGrouping($grouping);
        $this->add($collection);
    }

    /**
     * @return array
     */
    public function getNew()
    {
        $new = [];
        foreach ($this->getIterator() as $set) {
            $new = array_merge($new, $set->getNew());
        }

        return $new;
    }

    /**
     * @return array
     */
    public function getExisting()
    {
        $existing = [];
        foreach ($this->getIterator() as $set) {
            $existing = array_merge($existing, $set->getExisting());
        }

        return $existing;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
