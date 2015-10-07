<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Login;
use Bolt\AccessControl\Token;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for AccessControl\Login
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoginTest extends BoltUnitTest
{
    public function tearDown()
    {
        $this->resetDb();
    }

    public function testLoginNoCredentials()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Invalid login parameters.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);
        $request = new Request();

        $response = $login->login($request);
        $this->assertFalse($response);
    }

    public function testLoginInvalidUsername()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Your account is disabled. Sorry about that.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);
        $request = new Request();

        $response = $login->login($request, 'koala', 'sneaky');
        $this->assertFalse($response);
    }

    public function testLoginDisabledUsername()
    {
        $this->markTestSkipped('See issue #4237 Unable to disable users');

        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo("Attempt to login with disabled account by 'admin'"));
        $app['logger.system'] = $logger;

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Your account is disabled. Sorry about that.'));
        $app['logger.flash'] = $logger;

        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);
        $userEntity = $repo->getUser('admin');
        $userEntity->setEnabled(false);
        $repo->save($userEntity);

        $login = new Login($app);
        $request = new Request();

        $response = $login->login($request, 'admin', 'sneaky');
        $this->assertFalse($response);
    }

    public function testLoginWrongPassword()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['info'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->equalTo("Failed login attempt for 'Admin'."));
        $app['logger.system'] = $logger;

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Username or password not correct. Please check your input.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);
        $request = new Request();

        $response = $login->login($request, 'admin', 'sneaky');
        $this->assertFalse($response);
    }

    public function testLoginSuccessPassword()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['debug'], ['testlogger']);
        $logger->expects($this->at(0))
            ->method('debug')
            ->with($this->matchesRegularExpression('#Generating authentication cookie#'));
        $logger->expects($this->at(1))
            ->method('debug')
            ->with($this->matchesRegularExpression('#Saving new login token#'));
        $app['logger.system'] = $logger;

        $login = new Login($app);
        $request = Request::createFromGlobals();
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');

        $response = $login->login($request, 'admin', 'password');
        $this->assertTrue($response);
    }

    public function testLoginInvalidToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Invalid login parameters.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '1.2.3.4');
        $request->cookies->set($app['token.authentication.name'], 'abc123');

        $response = $login->login($request);
        $this->assertFalse($response);
    }

    public function testLoginExpiredToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Invalid login parameters.'));
        $app['logger.flash'] = $logger;

        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
        $entityAuthtoken = new \Bolt\Storage\Entity\Authtoken();
        $entityAuthtoken->setUsername('admin');
        $entityAuthtoken->setToken('abc123');
        $entityAuthtoken->setSalt('vinagre');
        $entityAuthtoken->setLastseen(Carbon::now());
        $entityAuthtoken->setIp('1.2.3.4');
        $entityAuthtoken->setUseragent('Bolt PHPUnit tests');
        $entityAuthtoken->setValidity(Carbon::create()->addHours(-1));
        $repo->save($entityAuthtoken);

        $login = new Login($app);
        $request = Request::createFromGlobals();

        $request->server->set('REMOTE_ADDR', '1.2.3.4');
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');
        $request->cookies->set($app['token.authentication.name'], 'abc123');

        $response = $login->login($request);
        $this->assertFalse($response);
    }

    public function testLoginUnsaltedToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['alert', 'debug'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('alert')
            ->with($this->equalTo('Attempt to login with an invalid token from 1.2.3.4'));
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->with($this->matchesRegularExpression('#Generating authentication cookie#'));
        $app['logger.system'] = $logger;

        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
        $entityAuthtoken = new \Bolt\Storage\Entity\Authtoken();
        $entityAuthtoken->setUsername('admin');
        $entityAuthtoken->setToken('abc123');
        $entityAuthtoken->setSalt('vinagre');
        $entityAuthtoken->setLastseen(Carbon::now());
        $entityAuthtoken->setIp('1.2.3.4');
        $entityAuthtoken->setUseragent('Bolt PHPUnit tests');
        $entityAuthtoken->setValidity(Carbon::create()->addHours(2));
        $repo->save($entityAuthtoken);

        $login = new Login($app);
        $request = Request::createFromGlobals();

        $request->server->set('REMOTE_ADDR', '1.2.3.4');
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');
        $request->cookies->set($app['token.authentication.name'], 'abc123');

        $response = $login->login($request);
        $this->assertFalse($response);
    }

    public function testLoginSuccessToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['debug'], ['testlogger']);
        $logger->expects($this->at(1))
            ->method('debug')
            ->with($this->matchesRegularExpression('#Generating authentication cookie#'));
        $logger->expects($this->at(2))
            ->method('debug')
            ->with($this->matchesRegularExpression("#Saving new login token#"));
        $app['logger.system'] = $logger;

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['success']);
        $logger->expects($this->at(0))
            ->method('success')
            ->with($this->equalTo('Session resumed.'));
        $logger->expects($this->at(1))
            ->method('success')
            ->with($this->equalTo("You've been logged on successfully."));
        $app['logger.flash'] = $logger;

        $userName = 'admin';
        $salt = 'vinagre';
        $ipAddress = '1.2.3.4';
        $hostName = 'bolt.dev';
        $userAgent = 'Bolt PHPUnit tests';
        $cookieOptions = [
            'remoteaddr'   => true,
            'httphost'     => true,
            'browseragent' => false,
        ];

        $token = (string) new Token\Generator($userName, $salt, $ipAddress, $hostName, $userAgent, $cookieOptions);

        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
        $entityAuthtoken = new \Bolt\Storage\Entity\Authtoken();
        $entityAuthtoken->setUsername($userName);
        $entityAuthtoken->setToken($token);
        $entityAuthtoken->setSalt($salt);
        $entityAuthtoken->setLastseen(Carbon::now());
        $entityAuthtoken->setIp('1.2.3.4');
        $entityAuthtoken->setUseragent('Bolt PHPUnit tests');
        $entityAuthtoken->setValidity(Carbon::create()->addHours(2));
        $repo->save($entityAuthtoken);

        $login = new Login($app);
        $request = Request::createFromGlobals();

        $request->server->set('REMOTE_ADDR', $ipAddress);
        $request->server->set('HTTP_USER_AGENT', $userAgent);
        $request->cookies->set($app['token.authentication.name'], $token);

        $response = $login->login($request);
        $this->assertTrue($response);
    }
}
