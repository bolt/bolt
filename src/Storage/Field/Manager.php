<?php
namespace Bolt\Storage\Field;

/**
 * Class to manage instances of fields and instantiate the defaults.
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0 see src/Storage/FieldManager
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/

class Manager
{
    protected $fields = [];

    protected $defaults = [
        'text', 'integer', 'float', 'geolocation', 'imagelist', 'image', 'file', 'filelist', 'video', 'hidden', 'html',
        'textarea', 'datetime', 'date', 'select', 'templateselect', 'templatefields', 'markdown', 'checkbox', 'slug',
        'repeater', 'block'
    ];

    protected $dummyFields = ['repeater', 'block'];

    public function __construct()
    {
        foreach ($this->defaults as $default) {
            $field = new Base($default, '@bolt/editcontent/fields/_' . $default . '.twig');
            $this->addField($field);
        }
    }

    public function addField(FieldInterface $field)
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0. Use \Bolt\Storage\FieldManager instead.', __METHOD__), E_USER_DEPRECATED);
        $this->fields[$field->getName()] = $field;
    }

    public function addDummyField($field)
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0. Use \Bolt\Storage\FieldManager instead.', __METHOD__), E_USER_DEPRECATED);
        $this->dummyFields[] = $field;
    }

    public function fields()
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0. Use \Bolt\Storage\FieldManager instead.', __METHOD__), E_USER_DEPRECATED);

        return $this->fields;
    }

    public function getField($name)
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0. Use \Bolt\Storage\FieldManager instead.', __METHOD__), E_USER_DEPRECATED);
        if ($this->has($name)) {
            return $this->fields[$name];
        } else {
            return false;
        }
    }

    public function getDatabaseField($field)
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0. Use \Bolt\Storage\FieldManager instead.', __METHOD__), E_USER_DEPRECATED);
        if (in_array($field, $this->dummyFields)) {
            return false;
        }

        return $this->getField($field);
    }

    public function has($field)
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0. Use \Bolt\Storage\FieldManager instead.', __METHOD__), E_USER_DEPRECATED);

        return isset($this->fields[$field]) || in_array($field, $this->dummyFields);
    }
}
