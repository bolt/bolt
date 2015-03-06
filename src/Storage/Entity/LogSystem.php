<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class LogSystem extends Entity
{
    
    protected $id;
    protected $level;
    protected $date;
    protected $message;
    protected $ownerid;
    protected $route;
    protected $ip;
    protected $context;
    protected $source;
    
}
