<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Configuration\ResourceManager;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Backend/Database.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class DatabaseTest extends BoltUnitTest
{
    public function testCheck()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('checkTablesIntegrity'), array($app));
        $check->expects($this->atLeastOnce())
            ->method('checkTablesIntegrity')
            ->will($this->returnValue(array('message', 'hint')));

        $app['integritychecker'] = $check;
        $request = Request::create('/bolt/dbcheck');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');

        $app->run($request);
    }

    public function testUpdate()
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

        $request = Request::create('/bolt/dbupdate', 'POST', array('return' => 'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/dbupdate', 'POST', array('return' => 'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/dbupdate', "POST");
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result?messages=null', $response->getTargetUrl());
    }

    public function testUpdateResult()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/dbupdate_result');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');

        $app->run($request);
    }
}
