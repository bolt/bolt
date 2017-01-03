<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Login;
use Bolt\AccessControl\Token;
use Bolt\Events\AccessControlEvent;
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

        $app['request_stack']->push(new Request());
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Login function called with empty username/password combination, or no authentication token.'));
        $app['logger.system'] = $logger;

        $login = new Login($app);

        $this->setExpectedException('Bolt\Exception\AccessControlException', 'Invalid login parameters.');
        $login->login(null, null, new AccessControlEvent(new Request()));
    }

    public function testLoginInvalidUsername()
    {
        $app = $this->getApp();
        $app['request_stack']->push(new Request());
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Username or password not correct. Please check your input.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);

        $response = $login->login('koala', 'sneaky', new AccessControlEvent(new Request()));
        $this->assertFalse($response);
    }


    public function testLoginDisabledUsername()
    {
        $app = $this->getApp();
        $app['request_stack']->push(new Request());
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['alert'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('alert')
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

        $response = $login->login('admin', 'sneaky', new AccessControlEvent(new Request()));
        $this->assertFalse($response);
    }

    public function testLoginDisabledUsernameWithCorrectPassword()
    {
        $app = $this->getApp();
        $app['request_stack']->push(new Request());
        $this->addDefaultUser($app);
        $this->addNewUser($app, 'koala', 'Koala', 'editor', false);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Your account is disabled. Sorry about that.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);

        $response = $login->login('koala', 'password', new AccessControlEvent(new Request()));
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

        $app['request_stack']->push(new Request());

        $login = new Login($app);

        $response = $login->login('admin', 'sneaky', new AccessControlEvent(new Request()));
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

        $request = Request::createFromGlobals();
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');
        $app['request_stack']->push($request);

        $login = new Login($app);

        $response = $login->login('admin', 'password', new AccessControlEvent($request));
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

        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '1.2.3.4');
        $request->cookies->set($app['token.authentication.name'], 'abc123');
        $app['request_stack']->push($request);

        $login = new Login($app);

        $response = $login->login(null, null, new AccessControlEvent(new Request()));
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

        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '1.2.3.4');
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');
        $request->cookies->set($app['token.authentication.name'], 'abc123');
        $app['request_stack']->push($request);

        $login = new Login($app);

        $response = $login->login(null, null, new AccessControlEvent(new Request()));
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

        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '1.2.3.4');
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');
        $request->cookies->set($app['token.authentication.name'], 'abc123');
        $app['request_stack']->push($request);

        $login = new Login($app);

        $response = $login->login(null, null, new AccessControlEvent(new Request()));
        $this->assertFalse($response);
    }

    public function testLoginSuccessToken()
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
        $hostName = 'bolt.test';
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

        $request = Request::createFromGlobals();

        $request->server->set('REMOTE_ADDR', $ipAddress);
        $request->server->set('HTTP_USER_AGENT', $userAgent);
        $request->cookies->set($app['token.authentication.name'], $token);
        $app['request_stack']->push($request);

        $login = new Login($app);

        $response = $login->login(null, null, new AccessControlEvent(new Request()));
        $this->assertTrue($response);
    }
}
