<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\AccessControl\Password;
use Bolt\Response\TemplateResponse;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
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
            'user_login' => [
                'login'    => '',
                'username' => 'test',
                'password' => 'pass',
                '_token'   => 'xyz',
            ],
        ]));

        $app = $this->getApp();
        $this->removeCSRF($app);
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(true));
        $this->setService('access_control.login', $loginMock);

        $this->setSessionUser(new Entity\Users(['username' => 'test', 'roles' => []]));
        $this->addDefaultUser($app);
        $response = $this->controller()->postLogin($this->getRequest());

        $this->assertTrue($response->isRedirect('/bolt'));
    }

    public function testPostLoginWithEmail()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'user_login' => [
                'login'    => '',
                'username' => 'test@example.com',
                'password' => 'pass',
                '_token'   => 'xyz',
            ],
        ]));

        $app = $this->getApp();
        $this->removeCSRF($app);
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

    public function testPostLoginFailure()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'user_login' => [
                'login'    => '',
                'username' => 'test',
                'password' => 'pass',
                '_token'   => 'xyz',
            ],
        ]));

        $app = $this->getApp();
        $this->removeCSRF($app);
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(false));
        $this->setService('access_control.login', $loginMock);
        /** @var TemplateResponse $response */
        $response = $this->controller()->getLogin($this->getRequest());
        $this->assertEquals('@bolt/login/login.twig', $response->getTemplate());
    }

    public function testLoginSuccess()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'user_login' => [
                'login'    => '',
                'username' => 'test',
                'password' => 'pass',
                '_token'   => 'xyz',
            ],
        ]));

        $app = $this->getApp();
        $this->removeCSRF($app);
        $loginMock = $this->getMockLogin();
        $loginMock->expects($this->once())
            ->method('login')
            ->will($this->returnValue(true));
        $this->setService('access_control.login', $loginMock);

        $this->setSessionUser(new Entity\Users(['username' => 'test', 'roles' => []]));

        $response = $this->controller()->getLogin($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt|', $response->getContent());
    }

    public function testResetRequest()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', [
            'user_login' => [
                'reset'    => '',
                'username' => 'admin',
                '_token'   => 'xyz',
            ],
        ]));

        $app = $this->getApp();
        $this->removeCSRF($app);

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

        // Test normal operation
        $response = $this->controller()->getLogin($this->getRequest());
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
     * @return Password|MockObject
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
