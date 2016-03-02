<?php
namespace Bolt\Storage\Entity;

/**
 * Provides access to entity attributes and the schema-less _fields
 * attribute via __get and __set magic methods.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
trait MagicAttributeTrait
{
    public $_fields = [];

    public function __get($key)
    {
        $method = 'get' . ucfirst($key);
        if (in_array($key, $this->getFields())) {
            return $this->$method();
        }
    }

    public function __set($key, $value)
    {
        $method = 'set' . ucfirst($key);
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
        $underscored = $this->underscore($var);

        if (strncasecmp($method, 'get', 3) == 0) {
            if ($this->has($var) && property_exists($this, $var)) {
                return $this->$var;
            } elseif ($this->has($underscored) && property_exists($this, $underscored)) {
                return $this->$underscored;
            } elseif ($this->has($var)) {
                return $this->_fields[$var];
            }
        }

        if (strncasecmp($method, 'serialize', 9) == 0) {
            $method = 'get' . substr($method, 9);

            return $this->$method();
        }

        if (strncasecmp($method, 'set', 3) == 0) {
            if ($this->has($var) && property_exists($this, $var)) {
                $this->$var = $arguments[0];
            } elseif ($this->has($underscored) && property_exists($this, $underscored)) {
                $this->$underscored = $arguments[0];
            } else {
                $this->_fields[$var] = $arguments[0];
            }
        }
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
     * Converts a string from underscored to Camel Case.
     *
     * @param string $id A string to camelize
     *
     * @return string The camelized string
     */
    public function camelize($id)
    {
        return strtr(ucwords(strtr($id, ['_' => ' ', '.' => '_ ', '\\' => '_ '])), [' ' => '']);
    }

    /**
     * Converts a string from camel case to underscored.
     *
     * @param string $id The string to underscore
     *
     * @return string The underscored string
     */
    public function underscore($id)
    {
        return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], str_replace('_', '.', $id)));
    }
}
