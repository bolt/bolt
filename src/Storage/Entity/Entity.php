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

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $method = 'set'.ucfirst($key);
            $this->$method($value);
        }
    }

    public function __toString()
    {
        return strval($this->getId());
    }

    public function getName()
    {
        return get_class($this);
    }
}
