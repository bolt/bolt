<?php
namespace Bolt\Storage\Mapping;

/**
 * Legacy bridge for ContentType array access.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ContentType implements \ArrayAccess
{
    /** @var string */
    protected $boltname;
    /** @var array */
    protected $contentType;

    /**
     * Constructor.
     *
     * @param string $boltname
     * @param array  $contentType
     */
    public function __construct($boltname, array $contentType)
    {
        $this->boltname = $boltname;
        $this->contentType = $contentType;
    }

    public function __toString()
    {
        return $this->boltname;
    }

    public function offsetSet($offset, $value)
    {
        $this->contentType[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->contentType[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->contentType[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->contentType[$offset]) ? $this->contentType[$offset] : null;
    }
    
    public function getFields()
    {
        if (isset($this->contentType['fields'])) {
            return $this->contentType['fields'];
        } else {
            return [];
        }
    }
}
