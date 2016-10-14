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

    protected $parameters;

    public function __construct($name, array $parameters)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->normalize();
        $this->validate();
    }

    public function normalize()
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
        return $this->parameters['type'];
    }

    public function getClass()
    {
        return $this->get('class', '');
    }

    public function getDefault()
    {
        return $this->get('defaults', '');
    }

    public function getInfo()
    {
        return $this->get('info', '');

    }

    public function getGroup()
    {
        return $this->get('group', 'ungrouped');
    }

    public function getLabel()
    {
        return $this->get('label', '');

    }

    public function getPattern()
    {
        return $this->get('pattern', '');
    }

    public function getPrefix()
    {
        return $this->get('prefix', '');

    }

    public function getPostfix()
    {
        return $this->get('postfix', '');
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

    protected function get($param, $default = null)
    {
        if ($this->has($param)) {
            return $this->parameters[$param];
        }

        return $default;
    }

    protected function set($param, $value)
    {
        $this->parameters[$param] = $value;
    }

    protected function has($param)
    {
        if (array_key_exists($this->parameters[$param]) && !empty($this->parameters[$param])) {
            return true;
        }

        return false;
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

    /**
     * Offset to unset - ArrayAccess method
     */
    public function offsetUnset($offset)
    {
        unset($this->parameters[$offset]);
    }
}