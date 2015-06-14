<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Relations extends Entity
{
    protected $id;
    protected $fromContenttype;
    protected $fromId;
    protected $toContenttype;
    protected $toId;
}
