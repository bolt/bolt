<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Token\Token;
use Bolt\Storage\Entity;

/**
 * Test for AccessControl\Token\Token
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TokenTokenTest extends BoltUnitTest
{
    public function testConstructor()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf('Bolt\AccessControl\Token\Token', $token);
    }

    public function testStringCast()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken(['token' => 'cookies']);
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf('Bolt\AccessControl\Token\Token', $token);
        $this->assertSame('cookies', (string) $token);
    }

    public function testIsEnabled()
    {
        $userEntity = new Entity\Users(['enabled' => true]);
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf('Bolt\AccessControl\Token\Token', $token);
        $this->assertTrue($token->isEnabled());

        $userEntity = new Entity\Users(['enabled' => false]);
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf('Bolt\AccessControl\Token\Token', $token);
        $this->assertFalse($token->isEnabled());
    }
}
