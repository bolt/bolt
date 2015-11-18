<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Taxonomy extends Entity
{
    protected $_config = [];
    protected $id;
    protected $content_id;
    protected $contenttype;
    protected $taxonomytype;
    protected $slug;
    protected $name;
    protected $sortorder = 0;

    public function setConfig(array $config)
    {
        $this->_config = $config;
    }
}
