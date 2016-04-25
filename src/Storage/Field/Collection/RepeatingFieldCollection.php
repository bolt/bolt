<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Exception\FieldConfigurationException;
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
    protected $mapping;
    protected $name;

    /**
     * RepeatingFieldCollection constructor.
     *
     * @param EntityManager $em
     * @param array         $elements
     */
    public function __construct(EntityManager $em, array $mapping, $elements = [])
    {
        $this->em = $em;
        $this->mapping = $mapping;
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
     * @param $entity
     *
     * @throws FieldConfigurationException
     */
    public function addFromArray(array $fields, $grouping = 0, $entity = null)
    {
        $collection = new FieldCollection([], $this->em);
        $collection->setGrouping($grouping);
        foreach ($fields as $name => $value) {
            $storageTypeHandler = $this->getFieldType($name);

            $field = new FieldValue();
            $field->setName($this->getName());
            $dbHandler = $storageTypeHandler->getStorageType();

            // We'd prefer data set from an array to already be correctly hydrated, but as a helper we here
            // pass it through the hydrate step if the value is a string. This will take care of cases where
            // a date/datetime is passed as string rather than an object.
            if (is_string($value)) {
                $field->setValue($dbHandler->convertToPHPValue($value, $storageTypeHandler->getPlatform()));
            } else {
                $field->setValue($value);
            }

            $field->setFieldname($name);
            if ($entity) {
                $field->setContenttype((string) $entity->contenttype);
            }
            $field->setFieldtype($this->getFieldTypeName($field->getFieldname()));
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
     * This loops over the existing collection to see if the properties in the incoming
     * are already available on a saved record.
     *
     * @param $entity
     *
     * @return mixed|null
     */
    public function getOriginal($entity)
    {
        $entities = $this->flatten();
        foreach ($entities as $existing) {
            if (
                $existing->getName() == $entity->getName() &&
                $existing->getGrouping() == $entity->getGrouping() &&
                $existing->getFieldname() == $entity->getFieldname()
            ) {
                return $existing;
            }
        }

        return $entity;
    }

    public function update($collection)
    {
        $updated = [];
        // First give priority to already existing entities
        foreach ($collection->flatten() as $entity) {
            $master = $this->getOriginal($entity);
            $master->setValue($entity->getValue());
            $master->setFieldtype($entity->getFieldtype());
            $master->handleStorage($this->getFieldType($entity->getFieldname()));

            $updated[] = $master;
        }

        $deleted = [];
        foreach ($this->flatten() as $old) {
            if (!in_array($old, $updated)) {
                $deleted[] = $old;
            }
        }

        // Clear the collection so that we re-add only the updated elements
        $this->clear();
        foreach ($updated as $new) {
            $this->add($new);
        }

        return $deleted;
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

    public function flatten()
    {
        $flat = [];
        foreach ($this as $collection => $vals) {
            $flat = array_merge($flat, array_values($vals->toArray()));
        }

        return $flat;
    }

    /**
     * @param $field
     *
     * @throws FieldConfigurationException
     *
     * @return mixed
     */
    protected function getFieldType($field)
    {
        if (!isset($this->mapping['data']['fields'][$field]['fieldtype'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }
        $mapping = $this->mapping['data']['fields'][$field];
        $setting = $mapping['fieldtype'];

        return $this->em->getFieldManager()->get($setting, $mapping);
    }

    /**
     * @param $field
     *
     * @throws FieldConfigurationException
     *
     * @return mixed
     */
    protected function getFieldTypeName($field)
    {
        if (!isset($this->mapping['data']['fields'][$field]['type'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }
        $mapping = $this->mapping['data']['fields'][$field];

        return $mapping['type'];
    }

    public function getEmptySet()
    {
        return new FieldCollection([], $this->em);
    }
}
