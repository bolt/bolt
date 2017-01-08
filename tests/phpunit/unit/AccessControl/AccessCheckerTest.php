<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Token\Token;
use Bolt\Storage\Entity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for AccessControl\AccessChecker
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessCheckerTest extends BoltUnitTest
{
    public function tearDown()
    {
        $this->resetDb();
    }

    public function testLoadAccessControl()
    {
        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);
    }

    public function testIsValidSessionNoCookie()
    {
        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);

        $this->setExpectedException('Bolt\Exception\AccessControlException', 'Can not validate session with an empty token.');

        $response = $accessControl->isValidSession(null);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage getAuthToken required a name and salt to be provided.
     */
    public function testIsValidSessionInvalidUsername()
    {
        $app = $this->getApp();

        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();

        $userEntity->setUsername(null);
        $tokenEntity->setSalt('vinagre');

        $token = new Token($userEntity, $tokenEntity);

        $app['session']->start();
        $app['session']->set('authentication', $token);

        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);

        $response = $accessControl->isValidSession($token);
        $this->assertFalse($response);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage getAuthToken required a name and salt to be provided.
     */
    public function testIsValidSessionInvalidSalt()
    {
        $app = $this->getApp();

        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();

        $userEntity->setUsername('koala');
        $tokenEntity->setSalt(null);

        $token = new Token($userEntity, $tokenEntity);

        $app['session']->start();
        $app['session']->set('authentication', $token);

        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);

        $response = $accessControl->isValidSession($token);
        $this->assertFalse($response);
    }

    public function testIsValidSessionGenerateToken()
    {
        $app = $this->getApp(false);

        $ipAddress = '8.8.8.8';
        $userAgent = 'Bolt PHPUnit tests';

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['info']);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->equalTo('You have been logged out.'));
        $app['logger.flash'] = $logger;

        $app->boot();

        $userEntity = new Entity\Users();
        $tokenEntity = new Entity\Authtoken();

        $userEntity->setUsername('koala');
        $tokenEntity->setToken('gum-leaves');
        $tokenEntity->setSalt('vinagre');
        $tokenEntity->setUseragent('Bolt PHPUnit tests');

        $token = new Token($userEntity, $tokenEntity);
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', $ipAddress);
        $request->server->set('HTTP_USER_AGENT', $userAgent);
        $request->cookies->set($app['token.authentication.name'], $token);
        $app['request_stack']->push($request);

        $app['session']->start();
        $app['session']->set('authentication', $token);

        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);

        $response = $accessControl->isValidSession($token);
        $this->assertFalse($response);
    }

    public function testIsValidSessionValidWithDbTokenNoDbUser()
    {
        $this->markTestIncomplete('Requires upcoming refactor of Repository DI');

        $app = $this->getApp();
        $this->addDefaultUser($app);

        $userName = 'koala';
        $salt = 'vinagre';
        $ipAddress = '8.8.8.8';
//         $hostName = 'bolt.test';
        $userAgent = 'Bolt PHPUnit tests';
//         $cookieOptions = [
//             'remoteaddr'   => true,
//             'httphost'     => true,
//             'browseragent' => false,
//         ];

//         $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['info']);
//         $logger->expects($this->atLeastOnce())
//             ->method('info')
//             ->with($this->equalTo('You have been logged out.'));
//         $app['logger.flash'] = $logger;

//         $app->boot();

        $userEntity = new Entity\Users();
        $userEntity->setUsername($userName);

        $tokenEntity = new Entity\Authtoken();
        $tokenEntity->setUsername($userName);
        $tokenEntity->setToken('gum-leaves');
        $tokenEntity->setSalt($salt);
        $tokenEntity->setIp($ipAddress);
        $tokenEntity->setUseragent('Bolt PHPUnit tests');

        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
        $repo->save($tokenEntity);

        $token = new Token($userEntity, $tokenEntity);
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', $ipAddress);
        $request->server->set('HTTP_USER_AGENT', $userAgent);
        $request->cookies->set($app['token.authentication.name'], $token);
        $app['request_stack']->push($request);

        $app['session']->start();
        $app['session']->set('authentication', $token);

        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);

        $mockAuth = $this->getMock('Bolt\Storage\Entity\Authtoken', ['getToken']);
        $mockAuth
            ->expects($this->once())
            ->method('getToken');
        $app['storage']->setRepository('Bolt\Storage\Entity\Authtoken', $mockAuth);

        $mockUser = $this->getMock('Bolt\Storage\Entity\Users', ['getUser']);
        $mockUser
            ->expects($this->never())
            ->method('getUser');
        $app['storage']->setRepository('Bolt\Storage\Entity\Users', $mockUser);

        $response = $accessControl->isValidSession($token);
        $this->assertFalse($response);
    }

    /**
     * @return \Bolt\AccessControl\AccessChecker
     */
    protected function getAccessControl()
    {
        $request = Request::createFromGlobals();
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');

        $app = $this->getApp();

        return $app['access_control'];
    }
}
