<?php

namespace Bolt\Tests\AccessControl;

use Bolt\AccessControl\Token\Generator;
use Bolt\Tests\BoltUnitTest;

/**
 * Test for AccessControl\Token\Generator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TokenGeneratorTest extends BoltUnitTest
{
    public function testGenerateEverything()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = '8.8.8.8';
        $hostName = 'tests.bolt.cm';
        $userAgent = 'Smith';
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => true,
            'browseragent' => true,
        ];

        $generator = new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);

        $this->assertSame('9b5573929c64c32c04f194e803397b5b2331c0e37710f18880919c7821012b00', (string) $generator);
    }

    public function testGenerateNoRemoteAddress()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = '8.8.8.8';
        $hostName = 'tests.bolt.cm';
        $userAgent = 'Smith';
        $cookieOptions = [
            'remoteaddr'   => false,
            'httphost'     => true,
            'browseragent' => true,
        ];

        $generator = new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);

        $this->assertSame('4821a933b29911764493b2c094481de4053386ed406e978ef57c9199eae4238f', (string) $generator);
    }

    public function testGenerateNoHttpHost()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = '8.8.8.8';
        $hostName = 'tests.bolt.cm';
        $userAgent = 'Smith';
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => false,
            'browseragent' => true,
        ];

        $generator = new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);

        $this->assertSame('3e242930d5d3f507d7b6e2b3b342b2d3b7af0dbbdd5687524cec3a1301dab91d', (string) $generator);
    }

    public function testGenerateNoBrowserAgent()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = '8.8.8.8';
        $hostName = 'tests.bolt.cm';
        $userAgent = 'Smith';
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => true,
            'browseragent' => false,
        ];

        $generator = new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);

        $this->assertSame('183b1f0a3f665b4f2f5c0da5583559b5852ddd6106e3dcb4ed7b6ba563dd2a4d', (string) $generator);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Token generator requires an IP address to be provided
     */
    public function testNullRemoteIp()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = null;
        $hostName = 'tests.bolt.cm';
        $userAgent = 'Smith';
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => true,
            'browseragent' => true,
        ];

        new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Token generator requires a remote host name to be provided
     */
    public function testNullHostName()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = '8.8.8.8';
        $hostName = null;
        $userAgent = 'Smith';
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => true,
            'browseragent' => true,
        ];

        new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Token generator requires a browser user agent to be provided
     */
    public function testNullUserAgent()
    {
        $username = 'koala';
        $salt = 'vinagre';
        $remoteIP = '8.8.8.8';
        $hostName = 'tests.bolt.cm';
        $userAgent = null;
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => true,
            'browseragent' => true,
        ];

        new Generator($username, $salt, $remoteIP, $hostName, $userAgent, $cookieOptions);
    }
}
