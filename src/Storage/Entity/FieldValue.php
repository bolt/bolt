<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for TemplateFields.
 */
class FieldValue extends Entity
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $contenttype;
    /** @var int */
    protected $content_id;
    /** @var string */
    protected $name;
    /** @var int */
    protected $grouping;
    /** @var string */
    protected $fieldname;
    /** @var string */
    protected $fieldtype;

    /** @var mixed */
    protected $value;

    /** @var string @internal Use $value instead */
    protected $value_string;
    /** @var string @internal Use $value instead */
    protected $value_text;
    /** @var integer @internal Use $value instead */
    protected $value_integer;
    /** @var double @internal Use $value instead */
    protected $value_float;
    /** @var integer @internal Use $value instead */
    protected $value_decimal;
    /** @var \DateTime @internal Use $value instead */
    protected $value_date;
    /** @var \DateTime @internal Use $value instead */
    protected $value_datetime;
    /** @var array @internal Use $value instead */
    protected $value_json_array = [];

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
