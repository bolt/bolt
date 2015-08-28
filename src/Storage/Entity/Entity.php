<?php
namespace Bolt\Storage\Entity;

use ArrayAccess;

/**
 * An abstract class that other entities can inherit. Provides automatic getters and setters along
 * with serialization.
 */
abstract class Entity implements ArrayAccess
{
    protected $_fields = [];
    protected $internal = ['contenttype'];

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $method = "set".ucfirst($key);
            $this->$method($value);
        }
    }

    public function __get($key)
    {
        $method = "get".ucfirst($key);
        if (in_array($key, $this->getFields())) {
            return $this->$method();
        }
    }

    public function __set($key, $value)
    {
        $method = "set".ucfirst($key);
        $this->$method($value);
    }

    public function __isset($key)
    {
        if ($this->has($key)) {
            return true;
        }
        return false;
    }

    public function __unset($key)
    {
        if ($this->has($key) && property_exists($this, $key)) {
            unset($this->$key);
        } elseif ($this->has($key)) {
            unset($this->_fields[$key]);
        }

        return false;
    }

    public function __call($method, $arguments)
    {
        $var = lcfirst(substr($method, 3));

        if (strncasecmp($method, "get", 3) == 0) {
            if ($this->has($var) && property_exists($this, $var)) {
                return $this->$var;
            } elseif ($this->has($var)) {
                return $this->_fields[$var];
            }
        }

        if (strncasecmp($method, "serialize", 9) == 0) {
            $method = 'get'.substr($method, 9);
            return $this->$method();
        }

        if (strncasecmp($method, "set", 3) == 0) {
            if ($this->has($var) && property_exists($this, $var)) {
                $this->$var = $arguments[0];
            } else {
                $this->_fields[$var] = $arguments[0];
            }
        }
    }

    public function __toString()
    {
        return strval($this->getId());
    }

    public function serialize()
    {
        $data = [];
        foreach ($this as $k => $v) {
            if (strpos($k, '_') === 0) {
                continue;
            }
            if (in_array($k, $this->internal)) {
                continue;
            }
            $method = "serialize".$k;
            $data[$k] = $this->$method();
        }

        foreach ($this->_fields as $k => $v) {
            $method = "serialize".$k;
            $data[$k] = $this->$method();
        }

        return $data;
    }

    public function jsonSerialize()
    {
        return $this->serialize();
    }

    public function toArray()
    {
        return $this->serialize();
    }

    public function getName()
    {
        return get_class($this);
    }

    /**
     * An internal method that builds a list of available fields depending on context
     *
     * @return array
     **/
    protected function getFields()
    {
        $fields = [];

        foreach ($this as $k => $v) {
            if (strpos($k, '_') !== 0) {
                $fields[] = $k;
            }
        }

        foreach ($this->_fields as $k => $v) {
            $fields[] = $k;
        }

        return $fields;
    }

    /**
     * Boolean check on whether entity has field
     *
     * @param string $field
     *
     * @return bool
     */
    protected function has($field)
    {
        return in_array($field, $this->getFields());
    }

    /**
     * @see ArrayAccess::offsetSet
     *
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value)
    {
        $accessor = "set".ucfirst($offset);
        $this->$accessor($value);
    }

    /**
     * @see ArrayAccess::offsetExists
     *
     * @param $offset
     */
    public function offsetExists($offset)
    {
        $accessor = "get".ucfirst($offset);
        $result = $this->$accessor();
        return !empty($result);
    }

    /**
     * @see ArrayAccess::offsetUnset
     *
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        $accessor = "set".ucfirst($offset);
        $this->$accessor(null);
    }

    /**
     * @see ArrayAccess::offsetGet
     *
     * @param $offset
     */
    public function offsetGet($offset)
    {
        $accessor = "get".ucfirst($offset);
        return $this->$accessor();
    }
}
