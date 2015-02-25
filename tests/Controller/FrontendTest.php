<?php
namespace Bolt\Tests\Controller;

use Bolt\Tests\BoltUnitTest;
use Bolt\Controllers\Frontend;
use Bolt\Content;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Frontend.
 * 
 * 
 * @author Ross Riley <riley.ross@gmail.com>
 **/

class FrontendTest extends BoltUnitTest
{
    
    public function setUp()
    {
        
    }

    public function testDefaultHomepage()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('index.twig');
        $app['twig'] = $twig;
        $response = Frontend::homepage($app);
    }
    
    public function testConfiguredHomepage()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $app['config']->set('general/homepage_template', 'custom-home.twig');
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('custom-home.twig');
        $app['twig'] = $twig;
        $response = Frontend::homepage($app);
    }
    
    public function testHomepageContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        $twig = $this->getMockTwig();
        $app['twig'] = $twig;

        $content1 = new Content($app);        
        
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($content1) );
            
        $app['storage'] = $storage;
        
        $response = Frontend::homepage($app);
        $globals = $app['twig']->getGlobals();
        $this->assertSame($content1, $globals['record']);
    }
    
    public function testMultipleHomepages()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        $twig = $this->getMockTwig();
        $app['twig'] = $twig;

        $content1 = new Content($app);
        $content2 = new Content($app);
        
        
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(
                array($content1, $content2)
            ));
            
        $app['storage'] = $storage;
        
        $response = Frontend::homepage($app);
        $globals = $app['twig']->getGlobals();
        $this->assertSame($content1, $globals['records'][0]);
        $this->assertSame($content2, $globals['records'][1]);
    }
    

    public function testRecord()
    {
        $app = $this->getApp();

        $contenttype = $app['storage']->getContentType('pages');
        $app['request'] = Request::create('/pages/test');
        $content1 = new Content($app, $contenttype);
        
        $storage = $this->getMock('Bolt\Storage', array('getContent', 'getContentType'), array($app));
        
        $storage->expects($this->once())
            ->method('getContent')
            ->with('pages')
            ->will($this->returnValue($content1) );
            
        $storage->expects($this->once())
            ->method('getContentType')
            ->with('pages')
            ->will($this->returnValue($contenttype) );
        $app['storage'] = $storage;
            
            
        $twig = $this->getMockTwig();
        $twig->expects($this->any())
            ->method('render')
            ->with('record.twig');
        $app['twig'] = $twig;
              

        Frontend::record($app, 'pages', 'test');

    }
    
    public function testNumericRecord()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/pages/', 'GET', array('id'=>5));
        $contenttype = $app['storage']->getContentType('pages');
        $content1 = new Content($app, $contenttype);

        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        
        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false) );
            
            
        $storage->expects($this->at(1))
            ->method('getContent')
            ->will($this->returnValue($content1) );
            
        $app['storage'] = $storage;
            
              

        $response = Frontend::record($app, 'pages', 5);

    }
    
    public function testNoRecord()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/pages/', 'GET', array('id'=>5));
        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        
        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false) );
            
        $app['storage'] = $storage;
        
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');   

        $response = Frontend::record($app, 'pages');

    }
    
    
    public function testRecordNoTemplate()
    {
            
        $app = $this->getApp();
        $app['request'] = Request::create('/pages/', 'GET', array('id'=>5));
        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        
        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false) );
            
        $app['storage'] = $storage;
        
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');           
        Frontend::record($app, 'pages');
    }
    
    public function testViewlessRecord()
    {
            
        $app = $this->getApp();
        $contenttype = $app['storage']->getContentType('pages');
        $contenttype['viewless'] = true;

        $app['request'] = Request::create('/pages/test');
        $storage = $this->getMock('Bolt\Storage', array('getContentType'), array($app));
        
        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contenttype) );
            
        $app['storage'] = $storage;
        
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');           
        Frontend::record($app, 'pages', 'test');
    }
    
    /**
     * @runInSeparateProcess
     *
     **/
    public function testPreview()
    {
        $app = $this->getApp();
        $request = Request::create('/pages/test');
        $app['request'] = $request;
        $templates = $this->getMock('Bolt\TemplateChooser', array('record'), array($app));
        $templates->expects($this->once())
            ->method('record')
            ->will($this->returnValue('record.twig'));
        $app['templatechooser'] = $templates;
        
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('record.twig');
        $app['twig'] = $twig;
        
        $response = Frontend::preview($request, $app, 'pages');     
    }
    
    public function testListing()
    {
        $app = $this->getApp();
        $request = Request::create('/pages');
        $app['request'] = $request;
        
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('listing.twig');
        $app['twig'] = $twig;
        
        $response = Frontend::listing($app, 'pages');     
    }
    
}
