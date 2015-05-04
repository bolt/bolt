<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Controller\Backend\Authentication;
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
        $this->setRequest(Request::create('/bolt/login', 'POST', array(
            'action'   => 'login',
            'username' => 'test',
            'password' => 'pass'
        )));

        $users = $this->getMock('Bolt\Users', array('login'), array($this->getApp()));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(true));
        $users->currentuser = array('username' => 'test', 'roles' => array());
        $this->setService('users', $users);

        $this->addDefaultUser($this->getApp());
        $response = $this->controller()->actionPostLogin($this->getRequest());

        $this->assertTrue($response->isRedirect('/bolt'));
    }

    public function testPostLoginWithEmail()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', array(
            'action'   => 'login',
            'username' => 'test@example.com',
            'password' => 'pass'
        )));

        $users = $this->getMock('Bolt\Users', array('login'), array($this->getApp()));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test@example.com'), $this->equalTo('pass'))
            ->will($this->returnValue(true));
        $users->currentuser = array('username' => 'test', 'email' => 'test@example.com', 'roles' => array());
        $this->setService('users', $users);

        $this->addDefaultUser($this->getApp());
        $response = $this->controller()->actionPostLogin($this->getRequest());

        $this->assertTrue($response->isRedirect('/bolt'));
    }

    public function testPostLoginFailures()
    {
        $this->setRequest(Request::create('/bolt/login', 'POST', array(
            'action'   => 'login',
            'username' => 'test',
            'password' => 'pass'
        )));

        $users = $this->getMock('Bolt\Users', array('login'), array($this->getApp()));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(false));

        $this->setService('users', $users);
        $this->checkTwigForTemplate($this->getApp(), 'login/login.twig');
        $this->controller()->actionPostLogin($this->getRequest());

        // Test missing data fails
        $this->setRequest(Request::create('/bolt/login', 'POST', array('action' => 'fake')));
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'Invalid request');
        $this->controller()->actionPostLogin($this->getRequest());

        $this->setRequest(Request::create('/bolt/login', 'POST', array()));
        $this->checkTwigForTemplate($this->getApp(), 'error.twig');
        $this->controller()->actionPostLogin($this->getRequest());
    }

    public function testLoginSuccess()
    {
        $users = $this->getMock('Bolt\Users', array('login'), array($this->getApp()));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
        $users->currentuser = array('username' => 'test', 'roles' => array());
        $this->setService('users', $users);

        $this->setRequest(Request::create('/bolt/login', 'POST', array('action' => 'login')));

        $response = $this->controller()->actionPostLogin($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt|', $response->getContent());
    }

    public function testResetRequest()
    {
        $dispatcher = $this->getService('swiftmailer.transport.eventdispatcher');
        $this->setService('swiftmailer.transport', new \Swift_Transport_NullTransport($dispatcher));

        $users = $this->getMock('Bolt\Users', array('login', 'resetPasswordRequest'), array($this->getApp()));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
        $users->expects($this->once())
        ->method('resetPasswordRequest')
            ->with($this->equalTo('admin'))
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        // Test missing username fails
        $this->setRequest(Request::create('/bolt/login', 'POST', array('action' => 'reset')));
        $response = $this->controller()->actionPostLogin($this->getRequest());
        $this->assertRegExp('/Please provide a username/i', $response->getContent());

        // Test normal operation
        $this->setRequest(Request::create('/bolt/login', 'POST', array('action' => 'reset', 'username' => 'admin')));
        $response = $this->controller()->actionResetPassword($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
    }

    public function testLogout()
    {
        $users = $this->getMock('Bolt\Users', array('logout'), array($this->getApp()));
        $users->expects($this->once())
            ->method('logout')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $this->setRequest(Request::create('/bolt/logout', 'POST', array()));

        $response = $this->controller()->actionLogout();
        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
    }

    public function testResetPassword()
    {
        $users = $this->getMock('Bolt\Users', array('resetPasswordConfirm'), array($this->getApp()));
        $users->expects($this->once())
            ->method('resetPasswordConfirm')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $this->setRequest(Request::create('/bolt/resetpassword'));

        $response = $this->controller()->actionResetPassword($this->getRequest());
        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
    }

    public function testDashboardWithoutPermissionRedirectsToHomepage()
    {
        $users = $this->getMock('Bolt\Users', array('hasUsers', 'isValidSession'), array($this->getApp()));
        $users->expects($this->any())
            ->method('hasUsers')
            ->will($this->returnValue(5));
        $users->expects($this->any())
            ->method('isValidSession')
            ->will($this->returnValue(true));
        $this->setService('users', $users);
        $this->getService('users')->currentuser = array('username' => 'test', 'roles' => array());
        $this->getService('config')->set('permissions/global/dashboard', array());

        $this->setRequest(Request::create('/bolt'));
        $response = $this->getService('controller.backend')->actionDashboard($this->getRequest());
        $this->assertTrue($response->isRedirect('/'), 'Failed to redirect to homepage');
    }

    /**
     * @return \Bolt\Controller\Backend\Authentication
     */
    protected function controller()
    {
        return $this->getService('controller.backend.authentication');
    }
}
