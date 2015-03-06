<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Relation extends Entity
{
    
    protected $id;
    protected $fromContenttype;
    protected $fromId;
    protected $toContenttype;
    protected $toId;
    
}
