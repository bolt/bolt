<?php
namespace Bolt\Tests\Controller;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Tests\BoltUnitTest;
use Bolt\Controllers\Extend;
use Bolt\Composer\CommandRunner;
use Bolt\Users;

/**
 * Class to test correct operation of src/Controllers/Extend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class ExtendControllerTest extends BoltUnitTest
{

    
    public function testDefaultRegistries()
    {
        
        $app = $this->getApp();
        $this->assertNotEmpty($app['extend.site']);
        $this->assertNotEmpty($app['extend.repo']);
        $runner = $app['extend.runner'];
        $this->assertInstanceOf(CommandRunner::class, $runner);
        
    }
    
    public function testMethodsReturnTemplates()
    {
        $app = $this->getApp();
        $app['twig.loader.filesystem']->prependPath(TEST_ROOT."/app/view/twig");
        $this->expectOutputRegex('#Redirecting to /bolt/#');
        $app->run();
        $extend = new Extend();
        $request = Request::create("/");
        $app['request'] = $request;
        $response = $extend->overview($app, $request);
        $this->assertRegExp('#<title>Extend[^<]*</title>#', $response);
        
        
        $response = $extend->installPackage($app, $request);
        $this->assertNotEmpty($response);
        
        
        // This currently checks for a live extension on the extend site. At some point we should mock this
        // But if it fails then replacing the package name will fix the test.
        $request = Request::create("/", "GET", array('package'=>'bolt/theme-2014'));
        $response = $extend->installInfo($app, $request);
        $this->assertNotEmpty($response);
        
        $request = Request::create("/", "GET", array('package'=>'bolt/theme-2014','version'=>'dev-master'));
        $response = $extend->packageInfo($app, $request);
        $this->assertNotEmpty($response);
        $content = json_decode($response->getContent());
        $this->assertAttributeNotEmpty('name', $content);
        
        $request = Request::create("/");
        $response = $extend->check($app, $request);
        $this->assertNotEmpty($response);
    }
    




}
