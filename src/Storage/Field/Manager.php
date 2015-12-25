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
        'textarea', 'datetime', 'date', 'select', 'templateselect', 'templatefields', 'markdown', 'checkbox', 'slug',
        'repeater',
    ];

    protected $dummyFields = ['repeater'];

    public function __construct()
    {
        foreach ($this->defaults as $default) {
            $field = new Base($default, '@bolt/editcontent/fields/_' . $default . '.twig');
            $this->addField($field);
        }
    }

    public function addField(FieldInterface $field)
    {
        $this->fields[$field->getName()] = $field;
    }

    public function addDummyField($field)
    {
        $this->dummyFields[] = $field;
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

    public function getDatabaseField($field)
    {
        if (in_array($field, $this->dummyFields)) {
            return false;
        }

        return $this->getField($field);
    }

    public function has($field)
    {
        return isset($this->fields[$field]) || in_array($field, $this->dummyFields);
    }
}
