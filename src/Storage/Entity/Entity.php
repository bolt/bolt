<?php

namespace Bolt\Storage\Entity;

use ArrayAccess;
use JsonSerializable;
use Serializable;

/**
 * An abstract class that other entities can inherit. Provides automatic getters and setters along
 * with serialization.
 */
abstract class Entity implements ArrayAccess, JsonSerializable, Serializable
{
    use MagicAttributeTrait;
    use EntitySerializeTrait;
    use EntityArrayAccessTrait;

    /** @var int */
    protected $id;
    /** @var array */
    protected $_internal = ['contenttype'];


    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            $this->$method($value);
        }
    }

    /**
     * Return an entity field value by name.
     *
     * @param string $key The entity field name
     *
     * @return mixed
     */
    public function get($key)
    {
        $method = 'get' . ucfirst($this->camelize($key));

        return $this->$method();
    }

    /**
     * Set an entity field value by name.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $method = 'set' . ucfirst($key);
        $this->$method($value);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $data = [];
        foreach ($this as $k => $v) {
            if (strpos($k, '_') === 0) {
                continue;
            }
            if (in_array($k, $this->_internal)) {
                continue;
            }
            $method = 'serialize' . $k;
            $data[$k] = $this->$method();
        }

        foreach ($this->_fields as $k => $v) {
            $method = 'serialize' . $k;
            $data[$k] = $this->$method();
        }

        return $data;
    }
}
