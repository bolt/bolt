<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Taxonomy extends Entity
{
    protected $_config = [];
    protected $id;
    protected $contentId;
    protected $contenttype;
    protected $taxonomytype;
    protected $slug;
    protected $name;
    protected $sortorder;


    public function setConfig(array $config)
    {
        $this->_config = $config;
    }

    public function setSlug($value)
    {

    }
}
