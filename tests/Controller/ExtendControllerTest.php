<?php
namespace Bolt\Tests\Controller;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Tests\BoltUnitTest;
use Bolt\Controllers\Extend;
use Bolt\Composer\PackageManager;
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
        $runner = $app['extend.manager'];
        $this->assertInstanceOf('Bolt\Composer\PackageManager', $runner);

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


        $request = Request::create("/", "GET", array('package'=>'bolt/theme-2014'));
        $extend = $this->getMock('Bolt\Controllers\Extend', array('installInfo', 'packageInfo', 'check'));
        $extend->expects($this->any())
            ->method('installInfo')
            ->will($this->returnValue(new Response('{"dev": [{"name": "bolt/theme-2014","version": "dev-master"}],"stable": []}')));

        $response = $extend->installInfo($app, $request);
        $this->assertNotEmpty($response);

        $request = Request::create("/", "GET", array('package'=>'bolt/theme-2014','version'=>'dev-master'));
        $extend->expects($this->any())
            ->method('packageInfo')
            ->will($this->returnValue(new Response('{"name":"bolt\/theme-2014","version":"unknown","type":"unknown","descrip":""}')));


        $response = $extend->packageInfo($app, $request);
        $this->assertNotEmpty($response);
        $content = json_decode($response->getContent());
        $this->assertAttributeNotEmpty('name', $content);

        $request = Request::create("/");

        $extend->expects($this->any())
            ->method('check')
            ->will($this->returnValue(new Response('{"updates":[],"installs":[]}')));

        $response = $extend->check($app, $request);
        $this->assertNotEmpty($response);
    }
    
    public function testOverview()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $request = Request::create('/bolt/extend/');
        $this->checkTwigForTemplate($app, 'extend/extend.twig');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testInstallPackage()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $request = Request::create('/bolt/extend/installPackage');
        $this->checkTwigForTemplate($app, 'extend/install-package.twig');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    
    public function testInstallInfo()
    {
        $app = $this->getApp();
        $mockInfo = $this->getMock('Bolt\Extensions\ExtensionsInfoService', array('info'), array(), 'MockInfoService', false);
        $mockInfo->expects($this->once())
            ->method('info')
            ->will($this->returnValue($this->packageInfoProvider()) );
        
        $app['extend.info'] = $mockInfo;
        
        $this->allowLogin($app);
        $request = Request::create('/bolt/extend/installInfo?package=test&bolt=2.0.0');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $parsedOutput = json_decode($response->getContent());
        $this->assertNotEmpty($parsedOutput->dev);
        $this->assertNotEmpty($parsedOutput->stable);
    }
    
    
    public function packageInfoProvider()
    {
        $info = array(
            'package' => 
                array(
                    'id' => '99999',
                    'title' => 'Test',
                    'source' => 'https://github.com/',
                    'name' => 'test',
                    'keywords' =>  array(),
                    'type' => 'bolt-extension',
                    'description' => 'Test',
                    'approved' => true,
                    'requirements' => 
                    array(
                      'bolt/bolt' => '>=2.0.0,<3.0.0',
                    ),
                    'versions' => 
                        array(
                          0 => '1.0.0',
                          1 => 'dev-master',
                        )
                ),
            'version' => 
                array(
                    array(
                          'name' => 'test',
                          'version' => '1.0.0',
                          'version_normalized' => '1.0.0.0',
                          'source' => 
                          array (
                            'type' => 'git',
                            'url' => 'https://github.com/',
                            'reference' => 'xxx',
                          ),
                          'require' => 
                          array (
                            'bolt/bolt' => '>=2.0.0,<3.0.0',
                          ),
                          'type' => 'bolt-extension',
                          'stability' => 'stable',
                          'buildStatus' => 'untested',
                    ),
                    array(
                        'name' => 'test',
                        'version' => 'dev-master',
                        'version_normalized' => '9999999-dev',
                        'source' => 
                            array(
                                'type' => 'git',
                                'url' => 'https://github.com/',
                                'reference' => 'XXX',
                            ),
                        'require' => array('bolt/bolt' => '>=2.0.0,<3.0.0'),
                        'type' => 'bolt-extension',
                        'stability' => 'dev',
                        'buildStatus' => 'untested',
                    )
                )
            );
        // This just ensures that the data matches the internal format of json decoded responses
        return json_decode(json_encode($info));
        
    }

}
