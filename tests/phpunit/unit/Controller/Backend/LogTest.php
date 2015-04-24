<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Configuration\ResourceManager;
use Bolt\Controllers\Backend\Log;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controllers/Backend/Log.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class LogTest extends BoltUnitTest
{
    public function testChangeOverview()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $log = $this->getMock('Bolt\Logger\Manager', array('clear', 'trim'), array($app));
        $log->expects($this->once())
            ->method('clear')
            ->will($this->returnValue(true));
        $log->expects($this->once())
            ->method('trim')
            ->will($this->returnValue(true));

        $app['logger.manager'] = $log;
        ResourceManager::$theApp = $app;

        $app['request'] = $request = Request::create('/bolt/changelog', 'GET', array('action' => 'trim'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $app['request'] = $request = Request::create('/bolt/changelog', 'GET', array('action' => 'clear'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/changelog', $response->getTargetUrl());

        $app['request'] = $request = Request::create('/bolt/changelog');
        $this->checkTwigForTemplate($app, 'activity/changelog.twig');
        $app->run($request);
    }

    public function testChangeRecord()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $controller = new Log();
        $controller->connect($app);

        $app['request'] = $request = Request::create('/bolt/changelog/pages/1/1');
        $response = $controller->actionChangeRecord($request, 'pages', 1, 1, $app, $request);
        $context = $response->getContext();
        $this->assertInstanceOf('Bolt\Logger\ChangeLogItem', $context['context']['entry']);

        // Test non-existing entry
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'exist');
        $app['request'] = $request = Request::create('/bolt/changelog/pages/1/100');
        $response = $controller->actionChangeRecord($request, 'pages', 1, 100, $app, $request);
        $context = $response->getContext();
    }

    public function testChangeRecordListing()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $controller = new Log();
        $controller->connect($app);

        // First test tests without any changelogs available
        $app['request'] = $request = Request::create('/bolt/changelog/pages');
        $response = $controller->actionChangeRecordListing($request, 'pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals(0, count($context['context']['entries']));
        $this->assertNull($context['context']['content']);
        $this->assertEquals('Pages', $context['context']['title']);
        $this->assertEquals('pages', $context['context']['contenttype']['slug']);

        // Search for a specific record where the content object doesn't exist
        $app['request'] = $request = Request::create('/bolt/changelog/pages/1');
        $response = $controller->actionChangeRecordListing($request, 'pages', 200, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('Page #200', $context['context']['title']);

        // This block generates a changelog on the page in question so we have something to test.
        $app['request'] = Request::create('/');
        $content = $app['storage']->getContent('pages/1');
        $content->setValues(array('status' => 'draft', 'ownerid' => 99));
        $app['storage']->saveContent($content, 'Test Suite Update');

        // Now handle all the other request variations
        $app['request'] = $request = Request::create('/bolt/changelog');
        $response = $controller->actionChangeRecordListing($request, null, null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('All content types', $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        $app['request'] = $request = Request::create('/bolt/changelog/pages');
        $response = $controller->actionChangeRecordListing($request, 'pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        $app['request'] = $request = Request::create('/bolt/changelog/pages/1');
        $response = $controller->actionChangeRecordListing($request, 'pages', '1', $app, $request);
        $context = $response->getContext();
        $this->assertEquals($content['title'], $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        // Test pagination
        $app['request'] = $request = Request::create('/bolt/changelog/pages', 'GET', array('page' => 'all'));
        $response = $controller->actionChangeRecordListing($request, 'pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertNull($context['context']['currentpage']);
        $this->assertNull($context['context']['pagecount']);

        $app['request'] = $request = Request::create('/bolt/changelog/pages', 'GET', array('page' => '1'));
        $response = $controller->actionChangeRecordListing($request, 'pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals(1, $context['context']['currentpage']);

        // Finally we delete the original content record, but make sure the logs still show
        $originalTitle = $content['title'];
        $app['storage']->deleteContent('pages', 1);
        $app['request'] = $request = Request::create('/bolt/changelog/pages/1');
        $response = $controller->actionChangeRecordListing($request, 'pages', '1', $app, $request);
        $context = $response->getContext();
        $this->assertEquals($originalTitle, $context['context']['title']);
        // Note the delete generates an extra log, hence the extra count
        $this->assertEquals(2, count($context['context']['entries']));
    }

    public function testSystemOverview()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $log = $this->getMock('Bolt\Logger\Manager', array('clear', 'trim'), array($app));
        $log->expects($this->once())
            ->method('clear')
            ->will($this->returnValue(true));
        $log->expects($this->once())
            ->method('trim')
            ->will($this->returnValue(true));

        $app['logger.manager'] = $log;
        ResourceManager::$theApp = $app;

        $app['request'] = $request = Request::create('/bolt/systemlog', 'GET', array('action' => 'trim'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $app['request'] = $request = Request::create('/bolt/systemlog', 'GET', array('action' => 'clear'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/systemlog', $response->getTargetUrl());

        $app['request'] = $request = Request::create('/bolt/systemlog');
        $this->checkTwigForTemplate($app, 'activity/systemlog.twig');
        $app->run($request);
    }
}
