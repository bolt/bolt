<?php
namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType record values.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentValuesTrait
{
    /**
     * Return a content objects values.
     *
     * @param boolean $json     Set to TRUE to return JSON encoded values for arrays
     * @param boolean $stripped Set to true to strip all of the base fields
     *
     * @return array
     */
    public function getValues($json = false, $stripped = false)
    {
    }

    /**
     * Set a Contenttype record's individual value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setValue($key, $value)
    {
    }

    /**
     * Set a Contenttype record's values.
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
    }
}
