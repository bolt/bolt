<?php

namespace Bolt\Storage\Entity;

/**
 * Provides ability for an entity to serialize itself.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
trait EntitySerializeTrait
{
    protected $_internal = ['contenttype'];

    public function serialize()
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
            $v = $this->$method();
            if ($v instanceof \Serializable) {
                $v = $v->serialize();
            }
            $data[$k] = $v;

        }

        foreach ($this->_fields as $k => $v) {
            $method = 'serialize' . $k;
            $v = $this->$method();
            if ($v instanceof \Serializable) {
                $v = $v->serialize();
            }
            $data[$k] = $v;
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
}
