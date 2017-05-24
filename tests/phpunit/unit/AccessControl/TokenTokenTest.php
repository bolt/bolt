<?php

namespace Bolt\Tests\AccessControl;

use Bolt\AccessControl\Token\Token;
use Bolt\Storage\Entity;
use Bolt\Tests\BoltUnitTest;

/**
 * Test for AccessControl\Token\Token.
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

        $this->assertInstanceOf(Token::class, $token);
    }

    public function testStringCast()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken(['token' => 'cookies']);
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame('cookies', (string) $token);
    }

    public function testIsEnabled()
    {
        $userEntity = new Entity\Users(['enabled' => true]);
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertTrue($token->isEnabled());

        $userEntity = new Entity\Users(['enabled' => false]);
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertFalse($token->isEnabled());
    }

    public function testGetUser()
    {
        $userEntity = new Entity\Users(['username' => 'koala']);
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);
        $user = $token->getUser();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertInstanceOf(Entity\Users::class, $user);
        $this->assertSame('koala', $user->getUsername());
    }

    public function testSetUser()
    {
        $userEntity = new Entity\Users(['username' => 'koala']);
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);

        $userEntity = new Entity\Users(['username' => 'clippy']);
        $token->setUser($userEntity);

        $user = $token->getUser();

        $this->assertInstanceOf(Entity\Users::class, $user);
        $this->assertSame('clippy', $user->getUsername());
    }

    public function testGetToken()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken(['token' => 'gum-leaves']);
        $token = new Token($userEntity, $tokenEntity);
        $authToken = $token->getToken();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertInstanceOf(Entity\AuthToken::class, $authToken);
        $this->assertSame('gum-leaves', $authToken->getToken());
    }

    public function testSetToken()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken(['token' => 'gum-leaves']);
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);

        $tokenEntity = new Entity\Authtoken(['token' => 'cookies']);
        $token->setToken($tokenEntity);

        $authToken = $token->getToken();

        $this->assertInstanceOf(Entity\Authtoken::class, $authToken);
        $this->assertSame('cookies', $authToken->getToken());
    }

    public function testGetChecked()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);

        $checked = $token->getChecked();
        $this->assertGreaterThan(time() - 1, $checked);
        $this->assertLessThanOrEqual(time(), $checked);
    }

    public function testSetChecked()
    {
        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();
        $token = new Token($userEntity, $tokenEntity);

        $this->assertInstanceOf(Token::class, $token);

        $token->setChecked();
        $checked = $token->getChecked();
        $this->assertGreaterThan(time() - 1, $checked);
        $this->assertLessThanOrEqual(time(), $checked);
    }
}
