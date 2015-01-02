<?php
namespace Bolt\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Tests\BoltUnitTest;
use Bolt\Configuration\ResourceManager;

/**
 * Class to test correct operation of src/Controller/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class BackendTest extends BoltUnitTest
{


    public function testDashboard()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addDefaultUser();
        $twig = $this->getTwig();
        $phpunit = $this;
        $testHandler = function($template, $context) use($phpunit) {
            $phpunit->assertEquals('dashboard/dashboard.twig', $template);
            $this->assertNotEmpty($context['context']);
            $this->assertArrayHasKey('latest', $context['context']);
            $this->assertArrayHasKey('suggestloripsum', $context['context']);
            return new Response;
        };
        
        $twig->expects($this->any())
            ->method('render')
            ->will($this->returnCallBack($testHandler));
        $this->allowLogin($app); 
        $app['render'] = $twig;       
        $request = Request::create('/bolt/');
        $app->run($request);
    }
    
    public function testPostLogin()
    {
        $app = $this->getApp();

        $request = Request::create('/bolt/login','POST', array('action'=>'login','username'=>'test','password'=>'pass'));
            
        $users = $this->getMock('Bolt\Users', array('login'), array($app));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(true));
        
        $app['users'] = $users;
        $app->run($request);
        $this->expectOutputRegex("/Redirecting to \/bolt\//");

    }
    
    public function testPostLoginFailures()
    {
        $app = $this->getApp();

        $request = Request::create('/bolt/login','POST', array('action'=>'login','username'=>'test','password'=>'pass'));
            
        $users = $this->getMock('Bolt\Users', array('login'), array($app));
        $users->expects($this->once())
            ->method('login')
            ->with($this->equalTo('test'), $this->equalTo('pass'))
            ->will($this->returnValue(false));
        
        $app['users'] = $users;
        $this->checkTwigForTemplate($app, 'login/login.twig');
        $app->run($request);
        
        
        // Test missing data fails
        $app = $this->getApp();
        $request = Request::create('/bolt/login','POST', array('action'=>'fake'));
        $this->checkTwigForTemplate($app, 'error.twig');
        $app->run($request);
        
        $app = $this->getApp();
        $request = Request::create('/bolt/login','POST', array());
        $this->checkTwigForTemplate($app, 'error.twig');
        $app->run($request);

    }
    
    public function testLoginSuccess()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('login'), array($app));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
        $app['users'] = $users;
        $request = Request::create('/bolt/login','POST', array('action'=>'login'));
        $this->expectOutputRegex("/Redirecting to \/bolt\//");
        $app->run($request);
    }
    
    public function testResetRequest()
    {
        $app = $this->getApp();
        $app['swiftmailer.transport'] = new \Swift_Transport_NullTransport($app['swiftmailer.transport.eventdispatcher']);
        $users = $this->getMock('Bolt\Users', array('login', 'resetPasswordRequest'), array($app));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
            
        $users->expects($this->once())
            ->method('resetPasswordRequest')
            ->with($this->equalTo('admin'))
            ->will($this->returnValue(true));
        
        $app['users'] = $users;        
        
        // Test missing username fails
        $request = Request::create('/bolt/login','POST', array('action'=>'reset'));
        $app->run($request);
        $this->assertSame(0, array_search('Please provide a username', $app['session']->getFlashBag()->get('error')));
        
        
        // Test normal operation
        $request = Request::create('/bolt/login','POST', array('action'=>'reset', 'username'=>'admin'));
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");
        $app->run($request);
    }
    
    public function testLogout()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('logout'), array($app));
        $users->expects($this->once())
            ->method('logout')
            ->will($this->returnValue(true));
            
        $app['users'] = $users;
        
        $request = Request::create('/bolt/logout','POST', array());
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");
        $app->run($request);
        
    }
    
    public function testResetPassword()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('resetPasswordConfirm'), array($app));
        $users->expects($this->once())
            ->method('resetPasswordConfirm')
            ->will($this->returnValue(true));
            
        $app['users'] = $users;
        $request = Request::create('/bolt/resetpassword');
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");
        
        $app->run($request);
    }
    
    public function testDbCheck()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('checkTablesIntegrity'), array($app));
        $check->expects($this->once())
            ->method('checkTablesIntegrity')
            ->will($this->returnValue(array('message', 'hint')));
            
        $app['integritychecker'] = $check;
        $request = Request::create('/bolt/dbcheck');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');
        
        $app->run($request);
    }
    
    public function testDbUpdate()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('repairTables'), array($app));
        
            
        $check->expects($this->at(1))
            ->method('repairTables')
            ->will($this->returnValue(""));
            
        $check->expects($this->at(2))
            ->method('repairTables')
            ->will($this->returnValue("Testing"));
            
        $app['integritychecker'] = $check;
        ResourceManager::setApp($app);

        $request = Request::create('/bolt/dbupdate', "POST", array('return'=>'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));     

        $request = Request::create('/bolt/dbupdate', "POST", array('return'=>'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());

        $request = Request::create('/bolt/dbupdate', "POST");
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result?messages=null', $response->getTargetUrl());
        
    }
    
    public function testDbUpdateResult()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        
        $request = Request::create('/bolt/dbupdate_result');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');
        
        $app->run($request);
    }
    
    
    
    protected function getTwig()
    {
        $twig = $this->getMock('Twig_Environment', array('render', 'fetchCachedRequest'));
        $twig->expects($this->any())
            ->method('fetchCachedRequest')
            ->will($this->returnValue(false));
        return $twig;
    }
    
    protected function checkTwigForTemplate($app, $testTemplate)
    {
        $twig = $this->getTwig();        
        
        $twig->expects($this->once())
            ->method('render')
            ->with($this->equalTo($testTemplate))
            ->will($this->returnValue(new Response));
            
        $app['render'] = $twig; 
    }
    
    protected function allowLogin($app)
    {
        $this->addDefaultUser();
        $users = $this->getMock('Bolt\Users', array('isValidSession','isAllowed'), array($app));
        $users->expects($this->any())
            ->method('isValidSession')
            ->will($this->returnValue(true));
            
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;
    }




}
