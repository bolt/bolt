<?php
namespace Bolt\Storage\Field;

/**
 * Class to manage instances of fields and instantiate the defaults.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/

class Manager
{
    protected $fields = [];

    protected $defaults = [
        'text', 'integer', 'float', 'geolocation', 'imagelist', 'image', 'file', 'filelist', 'video', 'hidden', 'html',
        'textarea', 'datetime', 'date', 'select', 'templateselect', 'templatefields', 'markdown', 'checkbox', 'slug'
    ];

    public function __construct()
    {
        foreach ($this->defaults as $default) {
            $field = new Base($default, 'editcontent/fields/_' . $default . '.twig');
            $this->addField($field);
        }
    }

    public function addField(FieldInterface $field)
    {
        $this->fields[$field->getName()] = $field;
    }

    public function fields()
    {
        return $this->fields;
    }

    public function getField($name)
    {
        if ($this->has($name)) {
            return $this->fields[$name];
        } else {
            return false;
        }
    }

    public function has($field)
    {
        return isset($this->fields[$field]);
    }
}
