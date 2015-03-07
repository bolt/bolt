<?php
namespace Bolt\Entity;

use Bolt\Entity\Entity;

/**
 * Entity for Auth Tokens.
 */
class LogChange extends Entity
{
    
    protected $id;
    protected $date;
    protected $ownerid;
    protected $title;
    protected $contenttype;
    protected $contentid;
    protected $mutationType;
    protected $diff;
    protected $comment;
    
}
