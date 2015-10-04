<?php
namespace Bolt\Tests\Controller;

use Bolt\Controller\Zone;
use Bolt\Legacy\Content;
use Bolt\Response\BoltResponse;
use Bolt\Storage;
use Bolt\Tests\Mocks\LoripsumMock;
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
        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');
        $app['dispatcher']->dispatch(KernelEvents::REQUEST, new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertEquals('frontend', Zone::get($request));
        $this->assertTrue(Zone::isFrontend($request));
    }

    public function testDefaultHomepageTemplate()
    {
        $this->setRequest(Request::create('/'));

        $response = $this->controller()->homepage();

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('index.twig', $response->getTemplateName());
    }

    public function testConfiguredHomepageTemplate()
    {
        $this->getService('config')->set('general/homepage_template', 'custom-home.twig');
        $this->setRequest(Request::create('/'));

        $response = $this->controller()->homepage();

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('custom-home.twig', $response->getTemplateName());
    }

    public function testHomepageContent()
    {
        $app = $this->getApp();
        $this->setRequest(Request::create('/'));

        $storage = $this->getMock('Bolt\Storage', ['getContent'], [$app]);
        $content1 = new Content($app);
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($content1));
        $this->setService('storage', $storage);

        $response = $this->controller()->homepage();
        $globals = $response->getGlobalContext();

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame($content1, $globals['records']);
    }

    public function testMultipleHomepages()
    {
        $this->setRequest(Request::create('/'));

        $content1 = new Content($this->getApp());
        $content2 = new Content($this->getApp());

        $storage = $this->getMock('Bolt\Storage', ['getContent'], [$this->getApp()]);
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(
                [$content1, $content2]
            ));
        $this->setService('storage', $storage);

        $globals = $this->controller()->homepage()->getGlobalContext();

        $this->assertSame($content1, $globals['records'][0]);
        $this->assertSame($content2, $globals['records'][1]);
    }

    public function testRecord()
    {
        $contenttype = $this->getService('storage')->getContentType('pages');
        $this->setRequest(Request::create('/pages/test'));
        $content1 = new Content($this->getApp(), $contenttype);

        $storage = $this->getMock('Bolt\Storage', ['getContent', 'getContentType'], [$this->getApp()]);

        $storage->expects($this->once())
            ->method('getContent')
            ->with('pages')
            ->will($this->returnValue($content1));

        $storage->expects($this->once())
            ->method('getContentType')
            ->with('pages')
            ->will($this->returnValue($contenttype));
        $this->setService('storage', $storage);

        $response = $this->controller()->record($this->getRequest(), 'pages', 'test');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('record.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    /**
     * @return array
     */
    public function testCanonicalUrlProvider()
    {
        return [
            ['http://bolt.dev/', null, false],
            ['http://bolt.dev/', null, true],
            ['https://foo.dev/', 'https://foo.dev/', false],
            ['https://foo.dev/', 'https://foo.dev/', true],
            ['http://bar.dev/', 'http://bar.dev/', false],
            ['http://bar.dev/', 'http://bar.dev/', true],
        ];
    }

    /**
     * @dataProvider testCanonicalUrlProvider
     */
    public function testCanonicalUrl($expected, $config_canonical, $use_https)
    {
        $this->getService('config')->set('general/homepage', 'showcase/1');

        if ($use_https) {
            $_SERVER['HTTPS'] == 'on';
            $_SERVER['SERVER_PORT'] == 443;
        }

        $this->setRequest(Request::create('/'));

        $templates = $this->getMock('Bolt\TemplateChooser', ['record'], [$this->getApp()]);
        $templates->expects($this->any())
            ->method('record')
            ->will($this->returnValue('index.twig'));
        $this->setService('templatechooser', $templates);

        $response = $this->controller()->record($this->getRequest(), 'showcase', '1');

        if ($config_canonical) {
            $this->getService('resources')->setUrl('canonicalurl', $config_canonical);
        }

        $canonical = $this->getService('resources')->getUrl('canonical');

        $this->assertEquals($expected, $canonical);

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('index.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testNumericRecord()
    {
        $this->setRequest(Request::create('/pages/', 'GET', ['id' => 5]));
        $contenttype = $this->getService('storage')->getContentType('pages');
        $content1 = new Content($this->getApp(), $contenttype);

        $storage = $this->getMock('Bolt\Storage', ['getContent'], [$this->getApp()]);

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));

        $storage->expects($this->at(1))
            ->method('getContent')
            ->will($this->returnValue($content1));

        $this->setService('storage', $storage);

        $response = $this->controller()->record($this->getRequest(), 'pages', 5);

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('record.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testNoRecord()
    {
        $this->setRequest(Request::create('/pages/', 'GET', ['id' => 5]));
        $storage = $this->getMock('Bolt\Storage', ['getContent'], [$this->getApp()]);

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));
        $this->setService('storage', $storage);

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');

        $response = $this->controller()->record($this->getRequest(), 'pages');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('record.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testRecordNoTemplate()
    {
        $this->setRequest(Request::create('/pages/', 'GET', ['id' => 5]));
        $storage = $this->getMock('Bolt\Storage', ['getContent'], [$this->getApp()]);

        $storage->expects($this->at(0))
            ->method('getContent')
            ->will($this->returnValue(false));
        $this->setService('storage', $storage);

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');

        $response = $this->controller()->record($this->getRequest(), 'pages');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('record.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testViewlessRecord()
    {
        $this->setRequest(Request::create('/pages/test'));

        $contenttype = $this->getService('storage')->getContentType('pages');
        $contenttype['viewless'] = true;

        $storage = $this->getMock('Bolt\Storage', ['getContentType'], [$this->getApp()]);
        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contenttype));
        $this->setService('storage', $storage);

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');

        $response = $this->controller()->record($this->getRequest(), 'pages', 'test');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('record.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    /**
     * @runInSeparateProcess
     **/
    public function testPreview()
    {
        $this->setRequest(Request::create('/pages'));
        $this->controller()->listing($this->getRequest(), 'pages/test');

        $templates = $this->getMock('Bolt\TemplateChooser', ['record'], [$this->getApp()]);
        $templates
            ->expects($this->any())
            ->method('record')
            ->will($this->returnValue('record.twig'));
        $this->setService('templatechooser', $templates);

        $response = $this->controller()->preview($this->getRequest(), 'pages');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('record.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testListing()
    {
        $this->setRequest(Request::create('/pages'));
        $response = $this->controller()->listing($this->getRequest(), 'pages');

        $this->assertSame('listing.twig', $response->getTemplateName());
        $this->assertTrue($response instanceof BoltResponse);
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testViewlessListing()
    {
        $this->setRequest(Request::create('/'));
        $contenttype = $this->getService('storage')->getContentType('pages');
        $contenttype['viewless'] = true;

        $storage = $this->getMock('Bolt\Storage', ['getContentType'], [$this->getApp()]);
        $storage->expects($this->once())
            ->method('getContentType')
            ->will($this->returnValue($contenttype));
        $this->setService('storage', $storage);

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not found');
        $response = $this->controller()->listing($this->getRequest(), 'pages');
        $this->assertTrue($response instanceof BoltResponse);
    }

    public function testBadTaxonomy()
    {
        $this->setRequest(Request::create('/faketaxonomy/main'));

        $storage = $this->getMock('Bolt\Storage', ['getTaxonomyType'], [$this->getApp()]);
        $storage->expects($this->once())
            ->method('getTaxonomyType')
            ->will($this->returnValue(false));
        $this->setService('storage', $storage);

        $response = $this->controller()->taxonomy($this->getRequest(), 'faketaxonomy', 'main');
        $this->assertFalse($response);
    }

    public function testNoContent404()
    {
        $this->setRequest(Request::create('/tags/fake'));

        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'No slug');

        $response = $this->controller()->taxonomy($this->getRequest(), 'tags', 'fake');
        $this->assertTrue($response instanceof BoltResponse);
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testTaxonomyListing()
    {
        $this->setRequest(Request::create('/categories/news'));
        $this->getService('config')->set('taxonomy/categories/singular_slug', 'categories');

        $response = $this->controller()->taxonomy($this->getRequest(), 'categories', 'news');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('listing.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testSimpleTemplateRender()
    {
        $this->setRequest(Request::create('/example'));

        $response = $this->controller()->template('index');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('index.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testFailingTemplateRender()
    {
        $this->setRequest(Request::create('/example'));

        // Test that the failure gets logged too.
//         $logger = $this->getMock('Bolt\DataCollector\TwigDataCollector', ['setTrackedValue'], [$this->getApp()]);
//         $logger->expects($this->once())
//             ->method('setTrackedValue')
//             ->with('templateerror');
//         $this->setService('twig.logger', $logger);

//         $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'failed');

        $response = $this->controller()->template('nonexistent');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('nonexistent.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testSearchListing()
    {
        $this->setRequest(Request::create('/search', 'GET', ['q' => 'Lorem']));

        $response = $this->controller()->search($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('listing.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testSearchWithFilters()
    {
        $this->setRequest(Request::create('/search', 'GET', [
            'search'          => 'Lorem',
            'pages_title'     => 1,
            'showcases_title' => 1,
            'pages_body'      => 1
        ]));

        $response = $this->controller()->search($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('listing.twig', $response->getTemplateName());
        $this->assertNotEmpty($response->getGlobalContext());
    }

    public function testBeforeHandlerForFirstUser()
    {
        $this->setRequest(Request::create('/'));

        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        $users->expects($this->once())
            ->method('getUsers')
            ->will($this->returnValue(false));
        $this->setService('users', $users);

        $response = $this->controller()->before($this->getRequest());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals('/bolt/userfirst', $response->getTargetUrl());
    }

    public function testBeforeHandlerForMaintenanceMode()
    {
        $this->setRequest(Request::create('/'));
        $this->getService('config')->set('general/maintenance_mode', true);

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
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

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
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

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $this->getService('config')->set('taxonomy/categories/options', ['news']);
        $prefillMock = new LoripsumMock();
        $this->setService('prefill', $prefillMock);

        $storage = new Storage($app);
        $storage->preFill(['showcases']);
    }

    /**
     * @return \Bolt\Controller\Frontend
     */
    protected function controller()
    {
        return $this->getService('controller.frontend');
    }
}
