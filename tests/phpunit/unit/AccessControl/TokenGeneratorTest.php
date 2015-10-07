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
}
