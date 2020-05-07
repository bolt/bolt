<?php

namespace Bolt\Tests\Controller;

use Bolt\Controller\Zone;
use Bolt\Legacy\Content;
use Bolt\Response\TemplateResponse;
use Bolt\Response\TemplateView;
use Bolt\TemplateChooser;
use Bolt\Twig\Runtime\HtmlRuntime;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class to test correct operation of src/Controller/Frontend.
 *
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class FrontendTest extends ControllerUnitTest
{
    /**
     * @covers \Bolt\Controller\Zone::get
     * @covers \Bolt\Controller\Zone::isFrontend
     */
    public function testControllerZone()
    {
        $app = $this->getApp();
        $this->setRequest(Request::create('/'));

        $request = $this->getRequest();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $app['dispatcher']->dispatch(KernelEvents::REQUEST, new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertEquals('frontend', Zone::get($request));
        $this->assertTrue(Zone::isFrontend($request));
    }

    public function testDefaultHomepageTemplate()
    {
        $this->setRequest(Request::create('/'));

        $response = $this->controller()->homepage($this->getRequest());

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('index.twig', $response->getTemplate());
    }

    /**
     * @group legacy
     */
    public function testLegacyDefaultHomepage()
    {
        $this->setRequest(Request::create('/'));
        $this->getService('config')->set('general/compatibility/template_view', false);

        $response = $this->controller()->homepage($this->getRequest());

        $this->assertTrue($response instanceof TemplateResponse);
        $this->assertSame('index.twig', $response->getTemplate());
    }

    public function testConfiguredConfigHomepageTemplate()
    {
        $this->getService('filesystem')->put('theme://custom-home.twig', '');
        $this->getService('config')->set('general/homepage_template', 'custom-home.twig');
        $this->setRequest(Request::create('/'));

        $response = $this->controller()->homepage($this->getRequest());

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('custom-home.twig', $response->getTemplate());
    }

    public function testConfiguredThemeHomepageTemplate()
    {
        $this->getService('filesystem')->put('theme://custom-theme-home.twig', '');
        $this->getService('config')->set('theme/homepage_template', 'custom-theme-home.twig');
        $this->setRequest(Request::create('/'));

        $response = $this->controller()->homepage($this->getRequest());

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('custom-theme-home.twig', $response->getTemplate());
    }

    public function testHomepageContent()
    {
        $this->setRequest(Request::create('/'));

        $response = $this->controller()->homepage($this->getRequest());
        $globals = $this->getTwigGlobals();

        $this->assertTrue($response instanceof TemplateView);
        $this->assertInstanceOf(Content::class, $globals['record']);
    }

    public function testMultipleHomepages()
    {
        $app = $this->getApp();
        $this->setRequest(Request::create('/'));
        $app['config']->set('general/homepage', 'pages');

        $this->controller()->homepage($this->getRequest());

        $globals = $this->getTwigGlobals();
        foreach ($globals['records'] as $record) {
            $this->assertInstanceOf(Content::class, $record);
        }
    }

    public function testRecord()
    {
        $response = $this->getRecordResponse();

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('page.twig', $response->getTemplate());
    }

    /**
     * @group legacy
     */
    public function testLegacyRecord()
    {
        $this->getService('config')->set('general/compatibility/template_view', false);
        $response = $this->getRecordResponse();

        $this->assertTrue($response instanceof TemplateResponse);
        $this->assertSame('page.twig', $response->getTemplate());
    }

    private function getRecordResponse()
    {
        $contentType = $this->getService('storage')->getContentType('pages');
        $request = Request::create('/pages/test');
        $this->setRequest($request);
        $content = new Content($this->getApp(), $contentType);
        $content->setValues(['slug' => 'test', 'title' => 'test']);
        $this->getService('storage')->saveContent($content);

        $response = $this->controller()->record($request, 'pages', 'test');

        return $response;
    }

    public function testCanonicalUrlForHomepage()
    {
        $expected = 'http://foo.dev/';

        /** @var \Silex\Application $app */
        $app = $this->getApp();
        $app['config']->set('general/homepage', 'page/1');

        $this->setRequest(Request::create($expected));
        $app['request_context']->fromRequest($this->getRequest());

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new GetResponseEvent($kernel, $this->getRequest(), HttpKernelInterface::MASTER_REQUEST);
        $app['canonical']->onRequest($event);

        $templates = $this->getMockBuilder(TemplateChooser::class)
            ->setMethods(['record'])
            ->setConstructorArgs([$app['config']])
            ->getMock()
        ;
        $templates->expects($this->any())
            ->method('record')
            ->will($this->returnValue('index.twig'));
        $this->setService('templatechooser', $templates);

        // Route for /page/1 instead of homepage
        $this->controller()->record($this->getRequest(), 'page', '1');

        $this->assertEquals($expected, $app['canonical']->getUrl(), 'Canonical url should be homepage');
    }

    public function testCanonicalUrlForNumericRecord()
    {
        /** @var \Silex\Application $app */
        $app = $this->getApp();
        $this->setService('twig.runtime.bolt_html', $this->getHtmlRuntime());

        $this->setRequest(Request::create('/pages/5'));
        $app['request_context']->fromRequest($this->getRequest());

        $contentType = $app['storage']->getContentType('pages');
        $content1 = new Content($app, $contentType);
        $content1->id = 5;
        $content1['slug'] = 'foo';

        $storage = $this->getMockStorage(['getContent']);
        $this->setService('storage', $storage);

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));

        $storage->expects($this->at(1))
            ->method('getContent')
            ->will($this->returnValue($content1));

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new GetResponseEvent($kernel, $this->getRequest(), HttpKernelInterface::MASTER_REQUEST);
        $app['canonical']->onRequest($event);

        // Route for /page/5 instead of /page/foo
        $this->controller()->record($this->getRequest(), 'pages', 5);

        $this->assertEquals('http://localhost/page/foo', $app['canonical']->getUrl(), 'Canonical url should use record slug instead of record ID');
    }

    public function testNumericRecord()
    {
        $this->setService('twig.runtime.bolt_html', $this->getHtmlRuntime());

        $this->setRequest(Request::create('/pages/', 'GET', ['id' => 5]));
        $contentType = $this->getService('storage')->getContentType('pages');
        $content1 = new Content($this->getApp(), $contentType);

        $storage = $this->getMockStorage(['getContent']);

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));

        $storage->expects($this->at(1))
            ->method('getContent')
            ->will($this->returnValue($content1));

        $this->setService('storage', $storage);

        $response = $this->controller()->record($this->getRequest(), 'pages', 5);

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('page.twig', $response->getTemplate());
    }

    /**
     * @return HtmlRuntime
     */
    private function getHtmlRuntime()
    {
        $app = $this->getApp();

        return new HtmlRuntime(
            $app['config'],
            $app['markdown'],
            $app['menu'],
            $app['storage']
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage not found
     */
    public function testNoRecord()
    {
        $this->setRequest(Request::create('/pages/', 'GET', ['id' => 5]));
        $storage = $this->getMockStorage();

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));
        $this->setService('storage', $storage);

        $this->controller()->record($this->getRequest(), 'pages');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage not found
     */
    public function testRecordNoTemplate()
    {
        $this->setRequest(Request::create('/pages/', 'GET', ['id' => 5]));
        $storage = $this->getMockStorage();

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));
        $this->setService('storage', $storage);

        $this->controller()->record($this->getRequest(), 'pages');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage not found
     */
    public function testViewlessRecord()
    {
        $this->setRequest(Request::create('/pages/test'));

        $contentType = $this->getService('storage')->getContentType('pages');
        $contentType['viewless'] = true;

        $storage = $this->getMockStorage();
        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contentType));
        $this->setService('storage', $storage);

        $this->controller()->record($this->getRequest(), 'pages', 'test');
    }

    public function testPreview()
    {
        $this->setRequest(Request::create('/pages', 'POST'));
        $this->controller()->listing($this->getRequest(), 'pages/test');

        $templates = $this->getMockBuilder(TemplateChooser::class)
            ->setMethods(['record'])
            ->setConstructorArgs([$this->getApp()['config']])
            ->getMock()
        ;
        $templates
            ->expects($this->any())
            ->method('record')
            ->will($this->returnValue('record.twig'))
        ;
        $this->setService('templatechooser', $templates);

        $response = $this->controller()->preview($this->getRequest(), 'pages');

        $this->assertFalse($response instanceof TemplateView);
    }

    public function testListing()
    {
        $this->setRequest(Request::create('/pages'));
        $response = $this->controller()->listing($this->getRequest(), 'pages');

        $this->assertSame('listing.twig', $response->getTemplate());
        $this->assertTrue($response instanceof TemplateView);
    }

    /**
     * @group legacy
     */
    public function testLegacyListing()
    {
        $this->getService('config')->set('general/compatibility/template_view', false);
        $this->setRequest(Request::create('/pages'));
        $response = $this->controller()->listing($this->getRequest(), 'pages');

        $this->assertSame('listing.twig', $response->getTemplate());
        $this->assertTrue($response instanceof TemplateResponse);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage not found
     */
    public function testViewlessListing()
    {
        $this->setRequest(Request::create('/'));
        $contentType = $this->getService('storage')->getContentType('pages');
        $contentType['viewless'] = true;

        $storage = $this->getMockStorage();
        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contentType));
        $this->setService('storage', $storage);

        $response = $this->controller()->listing($this->getRequest(), 'pages');
        $this->assertTrue($response instanceof TemplateView);
    }

    public function testBadTaxonomy()
    {
        $this->setRequest(Request::create('/faketaxonomy/main'));

        $response = $this->controller()->taxonomy($this->getRequest(), 'faketaxonomy', 'main');
        $this->assertFalse($response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage No slug
     */
    public function testNoContent404()
    {
        $this->setRequest(Request::create('/tags/fake'));

        $this->controller()->taxonomy($this->getRequest(), 'tags', 'fake');
    }

    public function testTaxonomyListing()
    {
        $this->setRequest(Request::create('/categories/news'));
        $this->getService('config')->set('taxonomy/categories/singular_slug', 'categories');

        $response = $this->controller()->taxonomy($this->getRequest(), 'categories', 'news');

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('listing.twig', $response->getTemplate());
    }

    /**
     * @group legacy
     */
    public function testLegacyTaxonomyListing()
    {
        $this->getService('config')->set('general/compatibility/template_view', false);
        $this->setRequest(Request::create('/categories/news'));
        $this->getService('config')->set('taxonomy/categories/singular_slug', 'categories');

        $response = $this->controller()->taxonomy($this->getRequest(), 'categories', 'news');

        $this->assertTrue($response instanceof TemplateResponse);
        $this->assertSame('listing.twig', $response->getTemplate());
    }

    public function testSimpleTemplateRender()
    {
        $this->setRequest(Request::create('/example'));

        $response = $this->controller()->template('index');

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('index.twig', $response->getTemplate());
    }

    /**
     * @group legacy
     */
    public function testLegacyTemplate()
    {
        $this->getService('config')->set('general/compatibility/template_view', false);
        $this->setRequest(Request::create('/example'));

        $response = $this->controller()->template('index');

        $this->assertTrue($response instanceof TemplateResponse);
        $this->assertSame('index.twig', $response->getTemplate());
    }

    /**
     * @expectedException \Twig\Error\LoaderError
     * @expectedExceptionMessage Template "nonexistent.twig" is not defined.
     */
    public function testFailingTemplateRender()
    {
        $this->controller()->template('nonexistent');
    }

    public function testSearchListing()
    {
        $this->setRequest(Request::create('/search', 'GET', ['q' => 'Lorem']));

        $response = $this->controller()->search($this->getRequest());

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('search.twig', $response->getTemplate());
    }

    /**
     * @group legacy
     */
    public function testLegacySearch()
    {
        $this->getService('config')->set('general/compatibility/template_view', false);
        $this->setRequest(Request::create('/search', 'GET', ['q' => 'Lorem']));

        $response = $this->controller()->search($this->getRequest());

        $this->assertTrue($response instanceof TemplateResponse);
        $this->assertSame('search.twig', $response->getTemplate());
    }

    public function testSearchWithFilters()
    {
        $this->setRequest(Request::create('/search', 'GET', [
            'search'          => 'Lorem',
            'pages_title'     => 1,
            'showcases_title' => 1,
            'pages_body'      => 1,
        ]));

        $response = $this->controller()->search($this->getRequest());

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('search.twig', $response->getTemplate());
    }

    public function testBeforeHandlerForFirstUser()
    {
        $this->setRequest(Request::create('/'));

        $users = $this->getMockUsers();

        $users->expects($this->once())
            ->method('getUsers')
            ->will($this->returnValue(false));
        $this->setService('users', $users);

        $response = $this->controller()->before($this->getRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/bolt/userfirst', $response->getTargetUrl());
    }

    public function testBeforeHandlerForMaintenanceMode()
    {
        $this->setRequest(Request::create('/'));
        $this->getService('config')->set('general/maintenance_mode', true);

        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $this->setService('permissions', $permissions);

        $response = $this->controller()->before($this->getRequest());
        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testBeforeHandlerForPrivilegedMaintenanceMode()
    {
        $this->setRequest(Request::create('/'));
        $this->getService('config')->set('general/maintenance_mode', true);

        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $response = $this->controller()->before($this->getRequest());

        $this->assertNull($response);
    }

    public function testNormalBeforeHandler()
    {
        $this->setRequest(Request::create('/'));
        $this->getService('config')->set('general/maintenance_mode', false);

        $response = $this->controller()->before($this->getRequest());

        $this->assertNull($response);
    }

    /**
     * @return \Bolt\Controller\Frontend
     */
    protected function controller()
    {
        return $this->getService('controller.frontend');
    }

    protected function getTwigGlobals()
    {
        $app = $this->getApp();

        return $app['twig']->getGlobals();
    }
}
