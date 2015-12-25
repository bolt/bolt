<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for TemplateFields.
 */
class FieldValue extends Entity
{
    protected $id;
    protected $value;
    protected $name;
    protected $grouping;
    protected $fieldname;
    protected $fieldtype;
    protected $contenttype;
    protected $content_id;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return (string) $this->getValue();
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

    /**
     *  When the entity needs to be persisted the value has to be copied to  a field specific to the storage type
     *  To do this we need a field type so we lookup the correct column to write to.
     *
     * @param $fieldObject
     */
    public function handleStorage($fieldObject)
    {
        $type = $fieldObject->getStorageType();
        $typeCol = 'value_' . $type->getName();
        $this->$typeCol = $this->getValue();
    }
}
