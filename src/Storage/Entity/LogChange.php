<?php
namespace Bolt\Storage\Entity;

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
