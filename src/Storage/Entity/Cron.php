<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Cron extends Entity
{
    
    protected $id;
    protected $lastrun;
    protected $interim;

}
