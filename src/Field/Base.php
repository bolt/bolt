<?php

namespace Bolt\Field;

class Base implements FieldInterface
{
    public $name;
    public $template;

    public function __construct($name, $template)
    {
        $this->name = $name;
        $this->template = $template;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getStorageOptions()
    {
        return array();
    }
}
