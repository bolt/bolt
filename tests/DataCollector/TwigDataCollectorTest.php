<?php
namespace Bolt\Tests\DataCollector;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\DataCollector\TwigDataCollector;
use Bolt\TwigExtension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\DBAL\Logging\DebugStack;

/**
 * Class to test src/DataCollector/TwigDataCollector.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class TwigDataCollectorTest extends BoltUnitTest
{

    
    public function testBasicData()
    {
        
        
        $app = $this->getApp();
        $app['twig']->addExtension(new TwigExtension($app));
        
        $app['log']->setValue('templatechosen', 'test');
        $app['log']->setValue('templateerror', 'error');
        
        $data = new TwigDataCollector($app);
        
        $request = Request::create('/','GET');
        $response = $app->handle($request);        
        
        $data->collect($request, $response);
        $this->assertEquals('twig', $data->getName());
        $this->assertTrue($data->getDisplayInWdt());
        $data->collectTemplateData('error.twig', array('test'));
        $this->assertGreaterThan(0, $data->getCountTemplates());
        $this->assertGreaterThan(0, $data->getCountFilters());
        $this->assertGreaterThan(0, $data->getCountFunctions());
        $this->assertGreaterThan(0, $data->getCountTests());
        $this->assertGreaterThan(0, $data->getCountExtensions());
        
        $this->assertEquals('error', $data->getTemplateError());
        //$this->assertEquals('test', $data->getChosenTemplate());
    }
    
    public function testCollectWithMocks()
    {
        $app = $this->getApp();
        $data = new TwigDataCollector($app);
        
        $request = Request::create('/','GET');
        $response = $app->handle($request);
        
        $ext = $this->getMock('\Twig_Extension');
        
        
        $filter = $this->getMock('\Twig_FilterInterface');
        $filter->expects($this->any())
            ->method('compile')
            ->will($this->returnValue(array(new \ArrayObject(array()), 'count')));
            
        $ext->expects($this->any())
            ->method('getFilters')
            ->will($this->returnValue(array('testfilter'=>$filter)));
        
        $test = $this->getMock("\Twig_TestInterface"); 
        $ext->expects($this->any())
            ->method('getTests')
            ->will($this->returnValue(array('test'=>$test)));
            
        $func = $this->getMock("\Twig_FunctionInterface"); 
        $ext->expects($this->any())
            ->method('getFunctions')
            ->will($this->returnValue(array('func'=>$func)));
        
        $app['twig']->addExtension($ext);
        $data->collect($request, $response);
    }
    
    
   
}
