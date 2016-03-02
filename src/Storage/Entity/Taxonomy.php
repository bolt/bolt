<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for taxonomy.
 */
class Taxonomy extends Entity
{
    /** @var array */
    protected $_config = [];
    /** @var int */
    protected $id;
    /** @var int */
    protected $content_id;
    /** @var string */
    protected $contenttype;
    /** @var string */
    protected $taxonomytype;
    /** @var string */
    protected $slug;
    /** @var string */
    protected $name;
    /** @var int */
    protected $sortorder = 0;

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getContentId()
    {
        return $this->content_id;
    }

    /**
     * @param int $content_id
     */
    public function setContentId($content_id)
    {
        $this->content_id = $content_id;
    }

    /**
     * @return string
     */
    public function getContenttype()
    {
        return $this->contenttype;
    }

    /**
     * @param string $contenttype
     */
    public function setContenttype($contenttype)
    {
        $this->contenttype = $contenttype;
    }

    /**
     * @return string
     */
    public function getTaxonomytype()
    {
        return $this->taxonomytype;
    }

    /**
     * @param string $taxonomytype
     */
    public function setTaxonomytype($taxonomytype)
    {
        $this->taxonomytype = $taxonomytype;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getSortorder()
    {
        return $this->sortorder;
    }

    /**
     * @param int $sortorder
     */
    public function setSortorder($sortorder)
    {
        $this->sortorder = $sortorder;
    }
}
