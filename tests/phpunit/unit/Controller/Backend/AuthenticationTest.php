<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\AccessControl\Password;
use Bolt\Logger\FlashLogger;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Backend/Authentication.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class AuthenticationTest extends ControllerUnitTest
{
    public function testPostLogin()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'action'   => 'login',
            'username' => 'test',
            'password' => 'pass',
        ]));

        $app = $this->getApp();
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(true));
        $this->setService('access_control.login', $loginMock);

        $this->setSessionUser(new Entity\Users(['username' => 'test', 'roles' => []]));
        $this->addDefaultUser($this->getApp());
        $response = $this->controller()->postLogin($this->getRequest());

        $this->assertTrue($response->isRedirect('/bolt'));
    }

    public function testPostLoginWithEmail()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'action'   => 'login',
            'username' => 'test@example.com',
            'password' => 'pass',
        ]));

        $app = $this->getApp();
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test@example.com'), $this->equalTo('pass'))
            ->will($this->returnValue(true));
        $this->setService('access_control.login', $loginMock);

        $this->setSessionUser(new Entity\Users(['username' => 'test', 'email' => 'test@example.com', 'roles' => []]));
        $this->addDefaultUser($this->getApp());
        $response = $this->controller()->postLogin($this->getRequest());

        $this->assertTrue($response->isRedirect('/bolt'));
    }

    public function testPostLoginFailures()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'action'   => 'login',
            'username' => 'test',
            'password' => 'pass',
        ]));

        $app = $this->getApp();
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(false));
        $this->setService('access_control.login', $loginMock);

        $this->checkTwigForTemplate($this->getApp(), '@bolt/login/login.twig');
        $this->controller()->postLogin($this->getRequest());

        // Test missing data fails
        $this->setRequest(Request::create('/bolt/login', 'POST', ['action' => 'fake']));
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'Invalid request');
        $this->controller()->postLogin($this->getRequest());

        $this->setRequest(Request::create('/bolt/login', 'POST', []));
        $this->checkTwigForTemplate($this->getApp(), 'error.twig');
        $this->controller()->postLogin($this->getRequest());
    }

    public function testLoginSuccess()
    {
        $app = $this->getApp();
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->will($this->returnValue(true));
        $this->setService('access_control.login', $loginMock);

        $this->setSessionUser(new Entity\Users(['username' => 'test', 'roles' => []]));

        $this->setRequest(Request::create('/bolt/login', 'POST', ['action' => 'login']));

        $response = $this->controller()->postLogin($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt|', $response->getContent());
    }

    public function testResetRequest()
    {
        $dispatcher = $this->getService('swiftmailer.transport.eventdispatcher');
        $this->setService('swiftmailer.transport', new \Swift_Transport_NullTransport($dispatcher));

        $this->setSessionUser(new Entity\Users());
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
        $this->setService('access_control.login', $loginMock);

        $passwordMock = $this->getMockPassword(['resetPasswordRequest']);
        $passwordMock->expects($this->once())
            ->method('resetPasswordRequest')
            ->with($this->equalTo('admin'))
            ->will($this->returnValue(true));
        $this->setService('access_control.password', $passwordMock);

        // Test missing username fails
        $this->setRequest(Request::create('/bolt/login', 'POST', ['action' => 'reset']));
        $flash = $this->prophesize(FlashLogger::class);
        $flash->get('info')->shouldBeCalled();
        $flash->get('success')->shouldBeCalled();
        $flash->get('error')->shouldBeCalled();
        $flash->error('Please provide a username')->shouldBeCalled();
        $this->setService('logger.flash', $flash->reveal());
        $this->controller()->postLogin($this->getRequest());

        // Test normal operation
        $this->setRequest(Request::create('/bolt/login', 'POST', ['action' => 'reset', 'username' => 'admin']));
        $response = $this->controller()->postLogin($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
    }

    public function testLogout()
    {
        $app = $this->getApp();
        $authentication = $this->getMockAccessChecker($app, ['revokeSession']);
        $authentication->expects($this->once())
            ->method('revokeSession')
            ->will($this->returnValue(true));
        $this->setService('access_control', $authentication);

        $this->setRequest(Request::create('/bolt/logout', 'POST', []));

        $response = $this->controller()->logout($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
    }

    public function testResetPassword()
    {
        $passwordMock = $this->getMockPassword(['resetPasswordConfirm']);
        $passwordMock->expects($this->once())
            ->method('resetPasswordConfirm')
            ->will($this->returnValue(true));
        $this->setService('access_control.password', $passwordMock);

        $this->setRequest(Request::create('/bolt/resetpassword'));
        $response = $this->controller()->resetPassword($this->getRequest());

        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
    }

    /**
     * @return \Bolt\Controller\Backend\Authentication
     */
    protected function controller()
    {
        return $this->getService('controller.backend.authentication');
    }

    /**
     * @param array $methods
     *
     * @return Password|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockPassword(array $methods)
    {
        return $this->getMockBuilder(Password::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
            ;
    }
}
