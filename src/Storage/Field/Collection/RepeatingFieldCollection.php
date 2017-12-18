<?php

namespace Bolt\Storage\Field\Collection;

use Bolt\Exception\FieldConfigurationException;
use Bolt\Storage\Entity\FieldValue;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\Type\FieldTypeBase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Fields.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RepeatingFieldCollection extends ArrayCollection
{
    /** @var EntityManager */
    protected $em;
    /** @var array */
    protected $mapping;
    /** @var string */
    protected $name;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param array         $mapping
     * @param array         $elements
     */
    public function __construct(EntityManager $em, array $mapping, $elements = [])
    {
        $this->em = $em;
        $this->mapping = $mapping;
        parent::__construct($elements);
    }

    /**
     * {@inheritdoc}
     */
    protected function createFrom(array $elements)
    {
        return new static($this->em, $this->mapping, $elements);
    }

    /**
     * @param FieldCollectionInterface $collection
     *
     * @return bool
     */
    public function add($collection)
    {
        return parent::add($collection);
    }

    /**
     * @param array  $fields
     * @param int    $grouping
     * @param object $entity
     * @param string $block
     *
     * @throws FieldConfigurationException
     */
    public function addFromArray(array $fields, $grouping = 0, $entity = null, $block = null)
    {
        $collection = new FieldCollection();
        $collection->setGrouping($grouping);
        $collection->setBlock($block);
        foreach ($fields as $name => $value) {
            $storageTypeHandler = $this->getFieldType($name, $block);

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
            $field->setFieldtype($this->getFieldTypeName($field->getFieldname(), $block));
            $field->setGrouping($grouping);
            $field->setBlock($block);
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
        $collection = new LazyFieldCollection($ids, $this->em);
        $collection->setGrouping($grouping);
        $this->add($collection);
    }

    /**
     * This loops over the existing collection to see if the properties in the incoming
     * are already available on a saved record.
     *
     * @param object $entity
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
                $existing->getFieldname() == $entity->getFieldname() &&
                (!$existing->getBlock() || $existing->getBlock() == $entity->getBlock())
            ) {
                return $existing;
            }
        }

        return $entity;
    }

    /**
     * @param RepeatingFieldCollection $collection
     *
     * @return RepeatingFieldCollection[]
     */
    public function update(RepeatingFieldCollection $collection)
    {
        $updated = [];
        // First give priority to already existing entities
        foreach ($collection->flatten() as $entity) {
            $master = $this->getOriginal($entity);
            $master->setValue($entity->getValue());
            $master->setFieldtype($entity->getFieldtype());
            $master->handleStorage($this->getFieldType($entity->getFieldname(), $entity->getBlock()));

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function flatten()
    {
        $flat = [];
        foreach ($this as $collection => $vals) {
            $flat = array_merge($flat, array_values($vals->toArray()));
        }

        return $flat;
    }

    /**
     * @param string      $field
     * @param string|null $block
     *
     * @throws FieldConfigurationException
     *
     * @return FieldTypeBase
     */
    protected function getFieldType($field, $block = null)
    {
        if ($block !== null && !isset($this->mapping['data']['fields'][$block]['fields'][$field]['fieldtype'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }

        if ($block === null && !isset($this->mapping['data']['fields'][$field]['fieldtype'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }

        if ($block !== null) {
            $mapping = $this->mapping['data']['fields'][$block]['fields'][$field];
        } else {
            $mapping = $this->mapping['data']['fields'][$field];
        }

        $setting = $mapping['fieldtype'];

        return $this->em->getFieldManager()->get($setting, $mapping);
    }

    /**
     * @param string $field
     * @param null   $block
     *
     * @throws FieldConfigurationException
     *
     * @return mixed
     */
    protected function getFieldTypeName($field, $block = null)
    {
        if ($block !== null && !isset($this->mapping['data']['fields'][$block]['fields'][$field]['type'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }

        if ($block === null && !isset($this->mapping['data']['fields'][$field]['type'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }

        if ($block !== null) {
            $mapping = $this->mapping['data']['fields'][$block]['fields'][$field];
        } else {
            $mapping = $this->mapping['data']['fields'][$field];
        }

        return $mapping['type'];
    }

    /**
     * @return FieldCollection
     */
    public function getEmptySet()
    {
        return new FieldCollection();
    }

    public function serialize()
    {
        $output = [];
        foreach ($this as $collection => $vals) {
            if ($vals->getBlock() !== null) {
                $output[$collection][$vals->getBlock()] = $vals->serialize();
            } else {
                $output[$collection] = $vals->serialize();
            }
        }

        return $output;
    }
}
