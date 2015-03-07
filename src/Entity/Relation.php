<?php
namespace Bolt\Entity;

use Bolt\Entity\Entity;

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
