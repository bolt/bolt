<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for TemplateFields.
 */
class TemplateFields extends Entity
{
    /**
     * Getter for templates using {{ content.get(title) }} functions.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $accessor = 'get'.ucfirst($key);
        return $this->$accessor();
    }
}
