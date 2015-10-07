<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Token\Generator;

/**
 * Test for AccessControl\Token\Generator
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

        $this->assertSame('a186bd81a42ade8d9db20d67f3e3dedb', (string) $generator);
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

        $this->assertSame('fab8d7c5234c667135c6272593801cde', (string) $generator);
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

        $this->assertSame('adf79fb05150a89782c040901b62e364', (string) $generator);
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

        $this->assertSame('87089069215f0b7f2a066c3ad132a5c2', (string) $generator);
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
