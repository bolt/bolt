<?php
namespace Bolt\AccessControl\Token;

/**
 * Token generator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Generator
{
    /** @var string */
    protected $token;

    public function __construct($username, $salt, $remoteIP, $hostName, $userAgent, array $cookieOptions)
    {
        if ($remoteIP === null) {
            throw new \InvalidArgumentException('Token generator requires an IP address to be provided');
        }
        if ($hostName === null) {
            throw new \InvalidArgumentException('Token generator requires a remote host name to be provided');
        }
        if ($userAgent === null) {
            throw new \InvalidArgumentException('Token generator requires a browser user agent to be provided');
        }

        $remoteIP = $cookieOptions['remoteaddr'] ? $remoteIP : '';
        $hostName = $cookieOptions['browseragent'] ? $userAgent : '';
        $userAgent = $cookieOptions['httphost'] ? $hostName : '';

        $this->token = md5($username.$salt.$remoteIP.$hostName.$userAgent);
    }

    public function __toString()
    {
        return $this->token;
    }
}
