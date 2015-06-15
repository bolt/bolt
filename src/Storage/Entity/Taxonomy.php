<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Taxonomy extends Entity
{
    protected $id;
    protected $contentId;
    protected $contenttype;
    protected $taxonomytype;
    protected $slug;
    protected $name;
    protected $sortorder;
}
