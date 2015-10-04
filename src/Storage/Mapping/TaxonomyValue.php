<?php
namespace Bolt\Storage\Mapping;

use Bolt\Exception\StorageException;

/**
 * Taxonomy mapping.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TaxonomyValue implements \ArrayAccess
{
    /** @var string */
    protected $name;
    /** @var string */
    protected $value;
    /** @var array */
    protected $data;

    /**
     * Constructor.
     *
     *
     * @param array $data
     *
     * @throws StorageException
     */
    public function __construct($name, $value, array $data)
    {
        if (empty($value)) {
            throw new StorageException('Taxonomy value can not be empty!');
        }

        $this->name = $name;
        $this->value = $value;
        $this->data = $data;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
