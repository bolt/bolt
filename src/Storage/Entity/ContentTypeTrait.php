<?php

namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType definitions.
 *
 * These traits should be considered transitional, the functionality in the
 * process of refactor, and not representative of a valid approach.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
trait ContentTypeTrait
{
    /**
     * Get field information for the given field.
     *
     * @param string $key
     *
     * @return array an associative array containing at least the key 'type',
     *               and, depending on the type, other keys
     */
    public function fieldInfo($key)
    {
        if (isset($this->contenttype['fields'][$key])) {
            return $this->contenttype['fields'][$key];
        } else {
            return ['type' => ''];
        }
    }

    /**
     * Get the field type for a given field name.
     *
     * @param string $key
     *
     * @return string
     */
    public function fieldType($key)
    {
        $field = $this->fieldInfo($key);

        return $field['type'];
    }

    public function next($field = 'datepublish', $where = [])
    {
        return $this->app['twig.runtime.bolt_record']->next($this, $field, $where);
    }

    public function previous($field = 'datepublish', $where = [])
    {
        return $this->app['twig.runtime.bolt_record']->previous($this, $field, $where);
    }
}
