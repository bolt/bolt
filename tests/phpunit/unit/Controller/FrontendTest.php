<?php
namespace Bolt\Tests\Controller;

use Bolt\Content;
use Bolt\Controller\Frontend;
use Bolt\Response\BoltResponse;
use Bolt\Storage;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Frontend.
 *
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 **/
class FrontendTest extends ControllerUnitTest
{
    public function testDefaultHomepageTemplate()
    {
        $this->setRequest(Request::create('/'));
        $response = $this->frontend()->homepage();
        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('index.twig', $response->getTemplateName());
    }

    public function testConfiguredHomepageTemplate()
    {
        $this->getService('config')->set('general/homepage_template', 'custom-home.twig');
        $this->setRequest(Request::create('/'));
        $response = $this->frontend()->homepage();
        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('custom-home.twig', $response->getTemplateName());
    }

    public function testHomepageContent()
    {
        $app = $this->getApp();
        $this->setRequest(Request::create('/'));

        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        $content1 = new Content($app);
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($content1));
        $app['storage'] = $storage;

        $response = $this->frontend()->homepage();
        $globals = $response->getGlobalContext();
        $this->assertSame($content1, $globals['record']);
    }

    public function testMultipleHomepages()
    {
        $app = $this->getApp();
        $this->setRequest(Request::create('/'));

        $content1 = new Content($app);
        $content2 = new Content($app);

        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(
                array($content1, $content2)
            ));
        $app['storage'] = $storage;

        $globals = $this->frontend()->homepage()->getGlobalContext();
        $this->assertSame($content1, $globals['records'][0]);
        $this->assertSame($content2, $globals['records'][1]);
    }

    public function testRecord()
    {
        $app = $this->getApp();

        $contenttype = $app['storage']->getContentType('pages');
        $app['request'] = $request = Request::create('/pages/test');
        $content1 = new Content($app, $contenttype);

        $storage = $this->getMock('Bolt\Storage', array('getContent', 'getContentType'), array($app));

        $storage->expects($this->once())
            ->method('getContent')
            ->with('pages')
            ->will($this->returnValue($content1));

        $storage->expects($this->once())
            ->method('getContentType')
            ->with('pages')
            ->will($this->returnValue($contenttype));
        $app['storage'] = $storage;

        $controller = $app['controller.frontend'];
        $response = $controller->record($request, 'pages', 'test');
    }

    public function testCanonicalUrl()
    {
        $app = $this->getApp();
        $app['config']->set('general/homepage', 'showcase/1');
        $app['request'] = $request = Request::create('/');
        $this->addDefaultUser($app);
        $this->addSomeContent();

        $templates = $this->getMock('Bolt\TemplateChooser', array('record'), array($app));
        $templates->expects($this->any())
            ->method('record')
            ->will($this->returnValue('index.twig'));
        $app['templatechooser'] = $templates;

        $controller = $app['controller.frontend'];
        $response = $controller->record($request, 'showcase', '1');
        $canonical = $app['resources']->getUrl('canonical');
        $this->assertEquals('http://bolt.dev/', $canonical);
    }

    public function testNumericRecord()
    {
        $app = $this->getApp();
        $app['request'] = $request = Request::create('/pages/', 'GET', array('id' => 5));
        $contenttype = $app['storage']->getContentType('pages');
        $content1 = new Content($app, $contenttype);

        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));

        $storage->expects($this->at(1))
            ->method('getContent')
            ->will($this->returnValue($content1));

        $app['storage'] = $storage;

        $controller = $app['controller.frontend'];
        $response = $controller->record($request, 'pages', 5);
    }

    public function testNoRecord()
    {
        $app = $this->getApp();
        $app['request'] = $request = Request::create('/pages/', 'GET', array('id' => 5));
        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));

        $app['storage'] = $storage;

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');

        $controller = $app['controller.frontend'];
        $response = $controller->record($request, 'pages');
    }

    public function testRecordNoTemplate()
    {
        $app = $this->getApp();
        $app['request'] = $request = Request::create('/pages/', 'GET', array('id' => 5));
        $storage = $this->getMock('Bolt\Storage', array('getContent'), array($app));

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));

        $app['storage'] = $storage;

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');
        $controller = $app['controller.frontend'];
        $controller->record($request, 'pages');
    }

    public function testViewlessRecord()
    {
        $app = $this->getApp();
        $contenttype = $app['storage']->getContentType('pages');
        $contenttype['viewless'] = true;

        $app['request'] = $request = Request::create('/pages/test');
        $storage = $this->getMock('Bolt\Storage', array('getContentType'), array($app));

        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contenttype));

        $app['storage'] = $storage;

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');
        $controller = $app['controller.frontend'];
        $controller->record($request, 'pages', 'test');
    }

    /**
     * @runInSeparateProcess
     **/
    public function testPreview()
    {
        $app = $this->getApp();
        $request = Request::create('/pages/test');
        $app['request'] = $request;
        $templates = $this->getMock('Bolt\TemplateChooser', array('record'), array($app));
        $templates->expects($this->any())
            ->method('record')
            ->will($this->returnValue('record.twig'));
        $app['templatechooser'] = $templates;

        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('record.twig');
        $app['twig'] = $twig;

        $controller = $app['controller.frontend'];
        $response = $controller->preview($request, 'pages');
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

        $controller = $app['controller.frontend'];
        $response = $controller->listing($request, 'pages');
    }

    public function testViewlessListing()
    {
        $app = $this->getApp();
        $contenttype = $app['storage']->getContentType('pages');
        $contenttype['viewless'] = true;

        $app['request'] = $request = Request::create('/pages');
        $storage = $this->getMock('Bolt\Storage', array('getContentType'), array($app));

        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contenttype));

        $app['storage'] = $storage;

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');
        $controller = $app['controller.frontend'];
        $controller->listing($request, 'pages');
    }

    public function testBadTaxonomy()
    {
        $app = $this->getApp();
        $request = Request::create('/faketaxonomy/main');
        $app['request'] = $request;

        $storage = $this->getMock('Bolt\Storage', array('getTaxonomyType'), array($app));

        $storage->expects($this->once())
            ->method('getTaxonomyType')
            ->will($this->returnValue(false));

        $app['storage'] = $storage;

        $controller = $app['controller.frontend'];
        $response = $controller->taxonomy($request, 'faketaxonomy', 'main');
        $this->assertFalse($response);
    }

    public function testNoContent404()
    {
        $app = $this->getApp();
        $request = Request::create('/tags/fake');
        $app['request'] = $request;

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'No slug');

        $controller = $app['controller.frontend'];
        $response = $controller->taxonomy($request, 'tags', 'fake');
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
        $controller = $app['controller.frontend'];
        $response = $controller->taxonomy($request, 'categories', 'news');
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

        $controller = $app['controller.frontend'];
        $response = $controller->template($request, 'index');
    }

    public function testFailingTemplateRender()
    {
        $app = $this->getApp();
        $request = Request::create('/example');
        $app['request'] = $request;

        // Test that the failure gets logged too.
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'failed');
        $controller = $app['controller.frontend'];
        $response = $controller->template($request, 'nonexistent');
    }

    public function testSearchListing()
    {
        $app = $this->getApp();
        $request = Request::create('/search', 'GET', array('q' => 'Lorem'));
        $app['request'] = $request;

        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('listing.twig');
        $app['twig'] = $twig;

        $controller = $app['controller.frontend'];
        $response = $controller->search($request, $app);
    }

    public function testSearchWithFilters()
    {
        $app = $this->getApp();
        $request = Request::create('/search', 'GET', array('search' => 'Lorem', 'pages_title' => 1, 'showcases_title' => 1, 'pages_body' => 1));
        $app['request'] = $request;

        $twig = $this->getMockTwig();
        $twig->expects($this->once())
            ->method('render')
            ->with('listing.twig');
        $app['twig'] = $twig;

        $controller = $app['controller.frontend'];
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
            ->will($this->returnValue(false));

        $app['users'] = $users;

        $controller = $app['controller.frontend'];
        $response = $controller->before($request);
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
            ->will($this->returnValue(false));

        $app['users'] = $users;

        $controller = $app['controller.frontend'];
        $response = $controller->before($request);
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
            ->will($this->returnValue(true));

        $app['users'] = $users;

        $controller = $app['controller.frontend'];
        $response = $controller->before($request);
        $this->assertNull($response);
    }

    public function testNormalBeforeHandler()
    {
        $app = $this->getApp();
        $request = Request::create('/');
        $app['request'] = $request;
        $app['config']->set('general/maintenance_mode', false);
        $controller = $app['controller.frontend'];
        $response = $controller->before($request);
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

    /**
     * @return \Bolt\Controller\Frontend
     */
    protected function frontend()
    {
        return $this->getService('controller.frontend');
    }
}
