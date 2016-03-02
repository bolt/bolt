<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for TemplateFields.
 */
class FieldValue extends Entity
{
    /** @var int */
    protected $id;
    /** @var mixed */
    protected $value;
    /** @var string */
    protected $name;
    protected $grouping;
    /** @var string */
    protected $fieldname;
    /** @var string */
    protected $fieldtype;
    protected $contenttype;
    /** @var int */
    protected $content_id;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return (string) $this->getValue();
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
     * When the entity needs to be persisted the value has to be copied to a field specific to the storage type
     *
     * To do this we need a field type so we lookup the correct column to write to.
     *
     * @param \Bolt\Storage\Field\Base $fieldObject
     */
    public function handleStorage($fieldObject)
    {
        $type = $fieldObject->getStorageType();
        $typeCol = 'value_' . $type->getName();
        $this->$typeCol = $this->getValue();
    }
}
