<?php
namespace Bolt\Tests\DataCollector;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\DataCollector\BoltDataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation and locations of src/DataCollector/BoltDataCollector.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BoltDataCollectorTest extends BoltUnitTest
{

    
    public function testBasicData()
    {
        $app = $this->getApp();
        
        
        $request = Request::create(
            '/',
            'GET',
            array(),
            array(),
            array(),
            array()
        );
        
        
        $this->expectOutputRegex('#Redirecting to /bolt/users/edit/#');
        $app->run($request);
        
        $response = new Response();
        
        
        $data = new BoltDataCollector($app);
        $data->collect($request, $response);
        $this->assertNotEmpty($data->getName());
        $this->assertNotEmpty($data->getVersion());
        $this->assertNotEmpty($data->getFullVersion());
        $this->assertNotNull($data->getVersionName());
        $this->assertNotEmpty($data->getPayoff());
        $this->assertNotEmpty($data->getAboutLink());
        $this->assertEmpty($data->getEditLink());
        $this->assertEmpty($data->getEditTitle());
    }
    
    public function testBrandingData()
    {
        $app = $this->getApp();
        $app['config']->set("general/branding/provided_by/0", 'testperson');
        $app['config']->set("general/branding/provided_by/1", 'testemail');
        $request = Request::create('/','GET');
        $this->expectOutputRegex('#Redirecting to /bolt/users/edit/#');
        $app->run($request);
        $response = new Response();
        $data = new BoltDataCollector($app);
        $data->collect($request, $response);
        $this->assertRegExp('/testperson/', $data->getBranding());
        $this->assertRegExp('/testemail/', $data->getBranding());
    }
    
    public function testEditLinks()
    {
        $app = $this->getApp();
        $app['editlink'] = "editlink";
        $app['edittitle'] = "edittitle";
        $request = Request::create('/','GET');
        $this->expectOutputRegex('#Redirecting to /bolt/users/edit/#');
        $app->run($request);
        $response = new Response();
        $data = new BoltDataCollector($app);
        $data->collect($request, $response);
        $this->assertEquals("editlink", $data->getEditlink());
        $this->assertEquals("edittitle", $data->getEdittitle());
        
    }

   
}
