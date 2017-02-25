<?php
namespace Bolt\Storage\Field;

use Bolt\Helpers\Deprecated;
use Bolt\Storage\FieldManager;

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
        Deprecated::method(null, FieldManager::class);

        $this->fields[$field->getName()] = $field;
    }

    public function addDummyField($field)
    {
        Deprecated::method(null, FieldManager::class);

        $this->dummyFields[] = $field;
    }

    public function fields()
    {
        Deprecated::method(null, FieldManager::class);

        return $this->fields;
    }

    public function getField($name)
    {
        Deprecated::method(null, FieldManager::class);

        if ($this->has($name)) {
            return $this->fields[$name];
        } else {
            return false;
        }
    }

    public function getDatabaseField($field)
    {
        Deprecated::method(null, FieldManager::class);

        if (in_array($field, $this->dummyFields)) {
            return false;
        }

        return $this->getField($field);
    }

    public function has($field)
    {
        Deprecated::method(null, FieldManager::class);

        return isset($this->fields[$field]) || in_array($field, $this->dummyFields);
    }
}
