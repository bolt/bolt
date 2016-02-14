<?php
namespace Bolt\Storage\Entity;

use ArrayAccess;
use JsonSerializable;

/**
 * An abstract class that other entities can inherit. Provides automatic getters and setters along
 * with serialization.
 */
abstract class Entity implements ArrayAccess, JsonSerializable
{
    use MagicAttributeTrait;
    use EntitySerializeTrait;
    use EntityArrayAccessTrait;

    /** @var int */
    protected $id;

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

    public function __toString()
    {
        return (string) $this->getId();
    }
}
