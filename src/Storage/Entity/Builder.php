<?php

namespace Bolt\Storage\Entity;

use Bolt\Storage\FieldManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\MetadataDriver;

/**
 * Builder class to create entity objects and populate with data.
 */
class Builder
{
    /**
     * The class to use for new instances.
     *
     * @var string
     */
    protected $class = 'Bolt\Storage\Entity\Content';
    protected $classMetadata;

    protected $metadata;
    protected $fieldManager;
    protected $transformers = [];

    public function __construct(MetadataDriver $metadata, FieldManager $fieldManager)
    {
        $this->metadata = $metadata;
        $this->fieldManager = $fieldManager;
    }

    /**
     * Sets the entity class that will be built.
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Sets the metadata for the class to be built.
     *
     * @param ClassMetadata $classMetadata
     */
    public function setClassMetadata(ClassMetadata $classMetadata)
    {
        $this->classMetadata = $classMetadata;
    }

    /**
     * Gets the metadata instance.
     *
     * @return ClassMetadata $classMetadata
     */
    public function getClassMetadata()
    {
        $class = $this->class;
        if ($this->classMetadata === null) {
            $classMetadata = $this->metadata->loadMetadataForClass($class);
            $this->classMetadata = $classMetadata;
        }

        return $this->classMetadata;
    }

    /**
     * Adds a transformer for a specific field type.
     *
     * @param string   $fieldTypeClass the class of the field type to transform
     * @param callable $handler
     */
    public function setTransformer($fieldTypeClass, callable $handler)
    {
        $this->transformers[$fieldTypeClass] = $handler;
    }

    /**
     * Returns a new empty entity class.
     *
     * @return object
     */
    public function getEntity($entity = null)
    {
        if ($entity === null) {
            $class = $this->class;
            $entity = new $class();
        }

        if (!$entity->getContenttype() && $ct = $this->getClassMetadata()->getBoltName()) {
            $entity->setContenttype($ct);
        }

        return $entity;
    }

    /**
     * Uses either the class default or the supplied ClassMetadata to return
     * a list of fields for this entity.
     *
     * @param ClassMetadata|null $classMetadata
     *
     * @return array
     */
    public function getFields()
    {
        return $this->getClassMetadata()->getFieldMappings();
    }

    /**
     * Creates a new entity object.
     *
     * @param array|object $data Data to load into the entity.
     *
     * @return object $entity
     */
    public function create($data, $entity = null)
    {
        $entity = $this->getEntity($entity);
        $fields = $this->getFields();

        // set fields
        foreach ($fields as $key => $mapping) {
            $fieldType = $this->fieldManager->get($mapping['fieldtype'], $mapping);

            // If we have a transformer setup then this takes precedence
            $mappedType = $mapping['fieldtype'];
            $handler = isset($this->transformers[$mappedType]) ? $this->transformers[$mappedType] : null;

            if ($handler) {
                call_user_func_array($handler, [$entity, $data[$key]]);
            } else {
                call_user_func_array([$fieldType, 'set'], [$entity, $data[$key]]);
            }
        }

        return $entity;
    }

    /**
     * Performs database to PHP transforms before creating new entity.
     *
     * @param array              $data
     * @param ClassMetadata|null $classMetadata
     *
     * @return object $entity
     */
    public function createFromDatabaseValues($data, $entity = null)
    {
        $entity = $this->getEntity($entity);
        $fields = $this->getFields();

        // set fields
        foreach ((array)$fields as $key => $mapping) {
            $fieldType = $this->fieldManager->get($mapping['fieldtype'], $mapping);
            call_user_func_array([$fieldType, 'hydrate'], [$data, $entity]);
        }

        return $entity;
    }

    public function refresh($entity)
    {
        $fields = $this->getFields();

        foreach ((array)$fields as $key => $mapping) {
            $fieldType = $this->fieldManager->get($mapping['fieldtype'], $mapping);
            $getter = 'get'.ucFirst($key);
            $value = $entity->$getter();
            if ($value) {
                call_user_func_array([$fieldType, 'set'], [$entity, $value]);
            }
        }
    }
}
