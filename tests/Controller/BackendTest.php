<?php
namespace Bolt\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Tests\BoltUnitTest;

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
        $this->checkTwigForTemplate($app, 'login/login.twig');
        $this->allowLogin($app); 
        $request = Request::create('/bolt/login','POST', array('action'=>'login'));
        $app->run($request);
        
        
        // Test missing data fails
        $request = Request::create('/bolt/login','POST', array('action'=>'fake'));
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
        $users = $this->getMock('Bolt\Users', array('login', 'resetPasswordRequest'), array($app));
        $users->expects($this->any())
            ->method('login')
            ->will($this->returnValue(true));
            
        $users->expects($this->any())
            ->method('resetPasswordRequest')
            ->will($this->returnValue(true));
        
        $app['users'] = $users;
        
        
        // Mock out the render method so we can test what is called
        $this->checkTwigForTemplate($app, 'login/login.twig');
        
        
        // Test missing username fails
        $request = Request::create('/bolt/login','POST', array('action'=>'reset'));
        $app->run($request);
        $this->assertSame(0, array_search('Please provide a username', $app['session']->getFlashBag()->get('error')));
        
        
        // Test normal operation
        $request = Request::create('/bolt/login','POST', array('action'=>'reset', 'username'=>'admin'));
        $this->checkTwigForTemplate($app, false);
        $this->expectOutputRegex("/Redirecting to \/bolt\/login/");
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
        $phpunit = $this;
        $testHandler = function($template, $context) use($phpunit, $testTemplate) {
            if($testTemplate) {
                $phpunit->assertEquals($testTemplate, $template);
            }
            return new Response;
        };
        
        $twig->expects($this->any())
            ->method('render')
            ->will($this->returnCallBack($testHandler));
        $this->allowLogin($app); 
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
