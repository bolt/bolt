<?php

namespace Bolt\Storage\Mapping;

use ArrayAccess;

/**
 * Mapping class to represent a ContentType with array access.
 *
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentType implements ArrayAccess
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

    public function setup()
    {

    }

    public function validate()
    {

    }

    public function getDefaultStatus()
    {
        return $this->get('default_status', 'draft');
    }

    public function getFields()
    {
        return $this->get('fields', []);
    }

    public function getIconMany()
    {
        return $this->get('icon_many', false);
    }

    public function getIconOne()
    {
        return $this->get('icon_one', false);
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getRelations()
    {
        return $this->get('relations', []);
    }

    public function getSearchable()
    {
        return $this->get('searchable', true);
    }

    public function getShowOnDashboard()
    {
        return $this->get('show_on_dashboard', true);
    }

    public function getSingularName()
    {
        return $this->get('singular_name');
    }

    public function getSingularSlug()
    {
        return $this->get('singular_slug');
    }

    public function getSlug()
    {
        return $this->get('slug');
    }

    public function getTaxonomy()
    {
        return $this->get('taxonomy', []);
    }

    public function getViewless()
    {
        return $this->get('viewless', false);
    }



    protected function get($param, $default = null)
    {
        if ($this->has($param)) {
            return $this->contentType[$param];
        }

        return $default;
    }

    protected function has($param)
    {
        if (array_key_exists($param, $this->contentType) && !empty($this->contentType[$param])) {
            return true;
        }

        return false;
    }

    public function __toString()
    {
        return $this->boltname;
    }

    /**
     *  ArrayAccess interface methods
     *
     */
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
        }

        return [];
    }
}
