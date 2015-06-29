<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for system logs.
 *
 * @method integer   getId()
 * @method integer   getLevel()
 * @method \DateTime getDate()
 * @method string    getMessage()
 * @method integer   getOwnerid()
 * @method string    getRoute()
 * @method string    getIp()
 * @method string    getContext()
 * @method array     getSource()
 * @method setId($id)
 * @method setLevel($level)
 * @method setDate(\DateTime $date)
 * @method setMessage($message)
 * @method setOwnerid($ownerid)
 * @method setRoute($route)
 * @method setIp($ip)
 * @method setContext($context)
 * @method setSource($source)
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
