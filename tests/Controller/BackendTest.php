<?php
namespace Bolt\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Tests\BoltUnitTest;
use Bolt\Configuration\ResourceManager;

/**
 * Class to test correct operation of src/Controller/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class BackendTest extends BoltUnitTest
{


    public function testDashboard()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $twig = $this->getMockTwig();
        $phpunit = $this;
        $testHandler = function($template, $context) use($phpunit) {
            $phpunit->assertEquals('dashboard/dashboard.twig', $template);
            $phpunit->assertNotEmpty($context['context']);
            $phpunit->assertArrayHasKey('latest', $context['context']);
            $phpunit->assertArrayHasKey('suggestloripsum', $context['context']);
            return new Response;
        };

        $twig->expects($this->any())
            ->method('render')
            ->will($this->returnCallBack($testHandler));
        $this->allowLogin($app);
        $app['render'] = $twig;
        $request = Request::create('/bolt/');
        $app->run($request);
    }

    public function testDbCheck()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('checkTablesIntegrity'), array($app));
        $check->expects($this->once())
            ->method('checkTablesIntegrity')
            ->will($this->returnValue(array('message', 'hint')));

        $app['integritychecker'] = $check;
        $request = Request::create('/bolt/dbcheck');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');

        $app->run($request);
    }

    public function testDbUpdate()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('repairTables'), array($app));


        $check->expects($this->at(0))
            ->method('repairTables')
            ->will($this->returnValue(""));

        $check->expects($this->at(1))
            ->method('repairTables')
            ->will($this->returnValue("Testing"));

        $app['integritychecker'] = $check;
        ResourceManager::$theApp = $app;

        $request = Request::create('/bolt/dbupdate', "POST", array('return'=>'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/dbupdate', "POST", array('return'=>'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/dbupdate', "POST");
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result?messages=null', $response->getTargetUrl());

    }

    public function testDbUpdateResult()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/dbupdate_result');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');

        $app->run($request);
    }

    public function testClearCache()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $cache = $this->getMock('Bolt\Cache', array('clearCache'));
        $cache->expects($this->at(0))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles'=>'1.txt', 'failedfiles'=>'2.txt')));

        $cache->expects($this->at(1))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles'=>'1.txt')));

        $app['cache'] = $cache;
        $request = Request::create('/bolt/clearcache');
        $this->checkTwigForTemplate($app, 'clearcache/clearcache.twig');
        $app->run($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('error'));

        $request = Request::create('/bolt/clearcache');
        $this->checkTwigForTemplate($app, 'clearcache/clearcache.twig');
        $app->run($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));
    }

    public function testChangeLog()
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

        $request = Request::create('/bolt/changelog', "GET", array('action'=>'trim'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/changelog', "GET", array('action'=>'clear'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/changelog', $response->getTargetUrl());

        $request = Request::create('/bolt/changelog');
        $this->checkTwigForTemplate($app, 'activity/changelog.twig');
        $app->run($request);

    }

    public function testSystemLog()
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

        $request = Request::create('/bolt/systemlog', "GET", array('action'=>'trim'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/systemlog', "GET", array('action'=>'clear'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/systemlog', $response->getTargetUrl());

        $request = Request::create('/bolt/systemlog');
        $this->checkTwigForTemplate($app, 'activity/systemlog.twig');
        $app->run($request);

    }

    public function testOmnisearch()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/omnisearch', "GET", array('q'=>'test'));
        $this->checkTwigForTemplate($app, 'omnisearch/omnisearch.twig');

        $app->run($request);
    }

    public function testPrefill()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/prefill');
        $this->checkTwigForTemplate($app, 'prefill/prefill.twig');

        $app->run($request);
    }

}
