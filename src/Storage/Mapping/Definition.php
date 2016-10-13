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
        if ($this->has('class')) {
            return $this->get('class');
        }

        return '';
    }

    public function getDefault()
    {
        if ($this->has('default')) {
            return $this->get('default');
        }

        return '';
    }

    public function getGroup()
    {
        if ($this->has('group')) {
            return $this->get('group');
        }

        return 'ungrouped';
    }

    public function getLabel()
    {
        if ($this->has('label')) {
            return $this->get('label');
        }

        return '';
    }

    public function getVariant()
    {
        if ($this->has('variant')) {
            return $this->get('variant');
        }

        return '';
    }

    protected function get($param)
    {
        $getter = 'get'.ucfirst($param);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        return $this->parameters[$param];
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