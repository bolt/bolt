<?php

namespace Bolt\Storage\Entity;

/**
 * Allows array access for an entity eg:
 *     $entity['value'] is equivalent to $entity->getValue().
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
trait EntityArrayAccessTrait
{
    /**
     * @see ArrayAccess::offsetSet
     *
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        $accessor = 'set' . ucfirst($offset);
        $this->$accessor($value);
    }

    /**
     * @see ArrayAccess::offsetExists
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (method_exists($this, 'has')) {
            // from MagicAttributeTrait
            return $this->has($offset);
        }
        $accessor = 'get' . ucfirst($offset);
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
        $accessor = 'set' . ucfirst($offset);
        $this->$accessor(null);
    }

    /**
     * @see ArrayAccess::offsetGet
     *
     * @param string $offset
     */
    public function offsetGet($offset)
    {
        $accessor = 'get' . ucfirst($offset);

        return $this->$accessor();
    }
}
