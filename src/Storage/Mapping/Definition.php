<?php

namespace Bolt\Storage\Mapping;

use ArrayAccess;
use Bolt\Helpers\Str;

/**
 * This is a base class that stores information about a contenttype field definition
 * Primarily these are defined in contenttypes.yml
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Definition implements ArrayAccess
{

    protected $name;
    protected $type;
    protected $class;
    protected $default;
    protected $error;
    protected $info;
    protected $group;
    protected $label;
    protected $pattern;
    protected $placeholder;
    protected $postfix;
    protected $prefix;
    protected $readonly;
    protected $separator;
    protected $title;
    protected $variant;

    /**
     * Definition constructor.
     * @param $name
     * @param array $parameters
     */
    public function __construct($name, array $parameters)
    {
        $this->name = $name;
        foreach ($parameters as $key => $param) {
            if (property_exists($this, $key)) {
                $this->$key = $param;
            }
        }
        $this->parameters = $parameters;
    }

    public function setup()
    {
        $this->name = str_replace('-', '_', strtolower(Str::makeSafe($this->name, true)));
    }

    public function validate()
    {
        if (!isset($this->parameters['type']) || empty($this->parameters['type'])) {
            $error = sprintf('Field "%s" has no "type" set.', $this->getName());

            throw new InvalidArgumentException($error);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getClass()
    {
        return $this->get('class', '');
    }

    protected function get($param, $default = null)
    {
        if ($this->has($param)) {
            return $this->$param;
        }

        return $default;
    }

    protected function has($param)
    {
        if (property_exists($this, $param) && !empty($this->$param)) {
            return true;
        }

        return false;
    }

    public function getDefault()
    {
        return $this->get('default', '');
    }

    public function getError()
    {
        return $this->get('error', '');
    }

    public function getInfo()
    {
        return $this->get('info', '');

    }

    public function getGroup()
    {
        return $this->get('group', '');
    }

    public function getLabel()
    {
        return $this->get('label', '');

    }

    public function getPattern()
    {
        return $this->get('pattern', '');
    }

    public function getPlaceholder()
    {
        return $this->get('placeholder', '');
    }

    public function getPostfix()
    {
        return $this->get('postfix', '');
    }

    public function getPrefix()
    {
        return $this->get('prefix', '');

    }

    public function getReadonly()
    {
        $res = $this->get('readonly');

        return $res ? true : false;
    }

    public function getSeparator()
    {
        $res = $this->get('separator');

        return $res ? true : false;
    }

    public function getTitle()
    {
        return $this->get('title', '');
    }

    public function getVariant()
    {
        return $this->get('variant', '');
    }

    /**
     * Whether a offset exists - ArrayAccess method
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to get - ArrayAccess method
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set - ArrayAccess method
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    protected function set($param, $value)
    {
        if (property_exists($this, $param)) {
            $this->$param = $value;
        }
    }

    /**
     * Offset to unset - ArrayAccess method
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (property_exists($this, $offset)) {
            unset($this->$offset);
        }
    }
}
