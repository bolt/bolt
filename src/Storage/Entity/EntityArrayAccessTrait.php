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
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value)
    {
        $accessor = 'set'.ucfirst($offset);
        $this->$accessor($value);
    }

    /**
     * @see ArrayAccess::offsetExists
     *
     * @param $offset
     */
    public function offsetExists($offset)
    {
        $accessor = 'get'.ucfirst($offset);
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
        $accessor = 'set'.ucfirst($offset);
        $this->$accessor(null);
    }

    /**
     * @see ArrayAccess::offsetGet
     *
     * @param $offset
     */
    public function offsetGet($offset)
    {
        $accessor = 'get'.ucfirst($offset);

        return $this->$accessor();
    }
}
