<?php
namespace Bolt\Entity;

/**
 * Entity for Auth Tokens.
 */
class Cron extends Entity
{
    protected $id;
    protected $lastrun;
    protected $interim;
}
