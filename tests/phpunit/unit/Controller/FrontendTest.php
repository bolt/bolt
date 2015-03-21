<?php
namespace Bolt\Tests\Controller;

use Bolt\Tests\BoltUnitTest;
use Bolt\Controllers\Frontend;
use Bolt\Content;
use Bolt\Storage;
use Bolt\Tests\Mocks\LoripsumMock;
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
        $controller = new Frontend();
        $response = $controller->homepage($app);
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
        $controller = new Frontend();
        $response = $controller->homepage($app);
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
        
        $controller = new Frontend();
        $response = $controller->homepage($app);        

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
        
        $controller = new Frontend();
        $response = $controller->homepage($app);        
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
              
        $controller = new Frontend();
        $response = $controller->record($app, 'pages', 'test');

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
            
              
        $controller = new Frontend();
        $response = $controller->record($app, 'pages', 5);

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

        $controller = new Frontend();
        $response = $controller->record($app, 'pages');

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
        $controller = new Frontend();
        $controller->record($app, 'pages');
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
        $controller = new Frontend();
        $controller->record($app, 'pages', 'test');
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
        
        $controller = new Frontend();
        $response = $controller->preview($request, $app, 'pages');     
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
        
        $controller = new Frontend();
        $response = $controller->listing($app, 'pages');     
    }
    
    public function testViewlessListing()
    {
            
        $app = $this->getApp();
        $contenttype = $app['storage']->getContentType('pages');
        $contenttype['viewless'] = true;

        $app['request'] = Request::create('/pages');
        $storage = $this->getMock('Bolt\Storage', array('getContentType'), array($app));
        
        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contenttype) );
            
        $app['storage'] = $storage;
        
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');           
        $controller = new Frontend();
        $controller->listing($app, 'pages');
    }
    
    public function testBadTaxonomy()
    {
        $app = $this->getApp();
        $request = Request::create('/faketaxonomy/main');
        $app['request'] = $request;
        
        $storage = $this->getMock('Bolt\Storage', array('getTaxonomyType'), array($app));
        
        $storage->expects($this->once())
            ->method('getTaxonomyType')
            ->will($this->returnValue(false) );
        
        $app['storage'] = $storage;
        
        $controller = new Frontend();
        $response = $controller->taxonomy($app, 'faketaxonomy', 'main');
        $this->assertFalse($response);
    }
    
    public function testNoContent404()
    {
        $app = $this->getApp();
        $request = Request::create('/tags/fake');
        $app['request'] = $request;
        
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'No slug');           

        $controller = new Frontend();
        $response = $controller->taxonomy($app, 'tags', 'fake');

    }
    
    public function testTaxonomyListing()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = Request::create('/categories/news');
        $app['request'] = $request;
        
        $storage = new Storage($app);

        
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('listing.twig');
        $app['twig'] = $twig;
        
        // Make sure the check tests both normal slug and singular
        $app['config']->set('taxonomy/categories/singular_slug', 'categories');
        $controller = new Frontend();
        $response = $controller->taxonomy($app, 'categories', 'news');     
    }
    
    public function testSimpleTemplateRender()
    {
        $app = $this->getApp();
        $request = Request::create('/example');
        $app['request'] = $request;
        
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('index.twig');
        $app['twig'] = $twig;
        
        $controller = new Frontend();
        $response = $controller->template($app, 'index');
    }
    
    public function testFailingTemplateRender()
    {
        $app = $this->getApp();
        $request = Request::create('/example');
        $app['request'] = $request;
        
        // Test that the failure gets logged too.
        $logger = $this->getMock('Bolt\DataCollector\TwigDataCollector', array('setTrackedValue'), array($app));
        $logger->expects($this->once())
            ->method('setTrackedValue')
            ->with('templateerror');
        $app['twig.logger'] = $logger;
        
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'failed');           
        $controller = new Frontend();
        $response = $controller->template($app, 'nonexistent');
    }
    
    public function testSearchListing()
    {
        $app = $this->getApp();
        $request = Request::create('/search', 'GET', array('q'=>'Lorem'));
        $app['request'] = $request;
        
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('listing.twig');
        $app['twig'] = $twig;
        
        $controller = new Frontend();
        $response = $controller->search($request, $app);
    }
    
    public function testSearchWithFilters()
    {
        $app = $this->getApp();
        $request = Request::create('/search', 'GET', array('search'=>'Lorem','pages_title'=>1, 'showcases_title'=>1, 'pages_body'=>1));
        $app['request'] = $request;
        
        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('listing.twig');
        $app['twig'] = $twig;
        
        $controller = new Frontend();
        $response = $controller->search($request, $app);
    }
    
    public function testBeforeHandlerForFirstUser()
    {
        $app = $this->getApp();
        $request = Request::create('/');
        $app['request'] = $request;
        
        
        $users = $this->getMock('Bolt\Users', array('getUsers'), array($app));
        
        $users->expects($this->once())
            ->method('getUsers')
            ->will($this->returnValue(false) );
        
        $app['users'] = $users;
        
        $controller = new Frontend();
        $response = $controller->before($request, $app);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals('/bolt/users/edit/', $response->getTargetUrl());
    }
    
    public function testBeforeHandlerForMaintenanceMode()
    {
        $app = $this->getApp();
        $request = Request::create('/');
        $app['request'] = $request;
        $app['config']->set('general/maintenance_mode', true);
        
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        
        $users->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(false) );
        
        $app['users'] = $users;
        
        $controller = new Frontend();
        $response = $controller->before($request, $app);
        $this->assertEquals(503, $response->getStatusCode());
    }
    
    public function testBeforeHandlerForPrivilegedMaintenanceMode()
    {
        $app = $this->getApp();
        $request = Request::create('/');
        $app['request'] = $request;
        $app['config']->set('general/maintenance_mode', true);
        
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        
        $users->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(true) );
        
        $app['users'] = $users;
        
        $controller = new Frontend();
        $response = $controller->before($request, $app);
        $this->assertNull($response);
    }
    
    public function testNormalBeforeHandler()
    {
        $app = $this->getApp();
        $request = Request::create('/');
        $app['request'] = $request;
        $app['config']->set('general/maintenance_mode', false);
        $controller = new Frontend();
        $response = $controller->before($request, $app);
        $this->assertNull($response);
    }
    
    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['config']->set('taxonomy/categories/options', array('news'));
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(array('showcases'));
    }
    
}
