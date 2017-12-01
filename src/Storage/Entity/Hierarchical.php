<?php

namespace Bolt\Storage\Entity;

use Bolt\Storage\Hierarchy;

/**
 * Entity for Hierarchical data.
 */
class Hierarchical extends Entity
{

    /**
     * @var Hierarchy $hierarchy
     */
    protected $_hierarchy;
    protected $content;

    public function jsonSerialize()
    {

        return null;
    }

    public function serialize()
    {

        return null;
    }

    public function __toString()
    {

        return '';
    }

    /**
     * Allows us to call Hierarchical methods without defining them all
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {

        if ($this->_hierarchy instanceof Hierarchy && method_exists($this->_hierarchy, $method)) {
            return call_user_func_array([
                $this->_hierarchy,
                $method
            ], $arguments);
        }

        return parent::__call($method, $arguments);
    }

    /**
     * Get Hierarchy out of this class
     *
     * @return \Bolt\Storage\Hierarchy
     */
    public function getHierarchy()
    {

        return $this->_hierarchy;
    }

    /**
     * Set Hierarchy in this class
     *
     * @param \Bolt\Storage\Hierarchy $hierarchy
     *
     * @return $this
     */
    public function setHierarchy(Hierarchy $hierarchy)
    {

        $this->_hierarchy = $hierarchy;

        return $this;
    }

    /**
     * Get the Content entity
     */
    public function getContent()
    {

        return $this->content;
    }

    /**
     * Set the Content entity
     *
     * @param \Bolt\Storage\Entity\Content $content
     *
     * @return $this
     */
    public function setContent(Content $content)
    {

        $this->content = $content;

        return $this;
    }

    /**
     * Get an array of children records
     *
     * @return array
     */
    public function children()
    {

        return array_filter($this->_hierarchy->getChildContent($this->content->contenttype->offsetGet('slug'), $this->content->id, true, 'datepublish'));
    }

    /**
     * Get an array of parent records
     *
     * @return array|null
     */
    public function parent()
    {

        if (isset($this->content->parentid) && $this->content->parentid !== 0) {
            return $this->_hierarchy->getContentById($this->content->contenttype->offsetGet('slug'), $this->content->parentid, true);
        }

        return null;
    }

    /**
     * Get an array of parent IDs.
     *
     * @return array
     */
    public function parents()
    {

        if (isset($this->content->parentid) && $this->content->parentid !== 0) {
            return $this->_hierarchy->getContentHierarchy($this->content->contenttype->offsetGet('slug'), $this->content->parentid, true);
        }

        return [];
    }

    /**
     * Get the top level parent ID.
     *
     * @return null|int
     */
    public function rootParent()
    {

        $parents = $this->parents();

        if (is_array($parents) && count($parents) > 0) {
            return array_reverse($parents)[0];
        }

        return null;
    }
}
