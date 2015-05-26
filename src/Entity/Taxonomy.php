<?php
namespace Bolt\Entity;

use Bolt\Entity\Entity;

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
