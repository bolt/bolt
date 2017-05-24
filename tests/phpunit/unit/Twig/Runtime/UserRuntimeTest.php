<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\UserRuntime;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Class to test Bolt\Twig\Runtime\UserRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserRuntimeTest extends BoltUnitTest
{
    protected function setUp()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
    }

    public function testGetUserById()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUser(1);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertArrayHasKey('email', $result);
    }

    public function testGetUserByUsername()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUser('admin');
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertArrayHasKey('email', $result);
    }

    public function testGetUserByEmail()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUser('admin@example.com');
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertArrayHasKey('email', $result);
    }

    public function testGetUserIdInvalid()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUserId(42);
        $this->assertFalse($result);
    }

    public function testGetUserIdById()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUserId(1);
        $this->assertSame(1, $result);
    }

    public function testGetUserIdByUsername()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUserId('admin');
        $this->assertSame(1, $result);
    }

    public function testGetUserIdByEmail()
    {
        $app = $this->getApp();
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->getUserId('admin@example.com');
        $this->assertSame(1, $result);
    }

    public function testIsAllowedObject()
    {
        $app = $this->getApp();
        $users = $this->getMockUsers();
        $users
            ->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->will($this->returnValue(true))
        ;
        $this->setService('users', $users);
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $content = new \Bolt\Legacy\Content($app, []);
        $result = $handler->isAllowed('koala', $content);
        $this->assertTrue($result);
    }

    public function testIsAllowedArray()
    {
        $app = $this->getApp();
        $users = $this->getMockUsers();
        $users
            ->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->will($this->returnValue(true))
        ;
        $this->setService('users', $users);
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->isAllowed('koala', []);
        $this->assertTrue($result);
    }

    public function testIsAllowedString()
    {
        $app = $this->getApp();
        $users = $this->getMockUsers();
        $users
            ->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->will($this->returnValue(true))
        ;
        $this->setService('users', $users);
        $handler = new UserRuntime($app['users'], $app['csrf']);

        $result = $handler->isAllowed('koala', 'clippy');
        $this->assertTrue($result);
    }

    public function testToken()
    {
        $app = $this->getApp();
        $tokenManager = new CsrfTokenManager(null, new SessionTokenStorage(new Session(new MockArraySessionStorage())));
        $this->setService('csrf', $tokenManager);
        $handler = new UserRuntime($app['users'], $app['csrf']);
        $token = $tokenManager->refreshToken('bolt');

        $this->assertSame($token->getValue(), $handler->token()->getValue());
    }
}
