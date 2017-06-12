<?php

namespace Bolt\Storage\Entity;

use Bolt\Helpers\Deprecated;

/**
 * Provides ability for an entity to serialize itself.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait EntitySerializeTrait
{
     /**
     * @internal
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @internal
     */
    public function serialize()
    {
        $data = $this->toArray();
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        if (isset($trace[0]['file'])) {
            Deprecated::warn('Calling serialize() on entities to return an array', 3.4, 'Use toArray() instead.');

            return $data;
        }

        return serialize($data);
    }

    /**
     * @internal
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Return a PHP or JSON serializable array.
     *
     * @return array
     */
    abstract public function toArray();
}
