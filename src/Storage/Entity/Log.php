<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Log extends Entity
{
    protected $id;
    protected $level;
    protected $date;
    protected $message;
    protected $username;
    protected $requesturi;
    protected $route;
    protected $ip;
    protected $file;
    protected $line;
    protected $contenttype;
    protected $contentid;
    protected $code;
    protected $dump;
}
