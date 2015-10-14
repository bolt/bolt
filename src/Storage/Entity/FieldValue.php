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
        return $this->getValue();
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
