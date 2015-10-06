<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Configuration\ResourceManager;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Backend/Database.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class DatabaseTest extends ControllerUnitTest
{
    /**
     * @covers \Bolt\Storage\Database\Schema\CheckResponse
     */
    public function testCheck()
    {
        $this->allowLogin($this->getApp());
        $checkResponse = new \Bolt\Storage\Database\Schema\CheckResponse();
        $check = $this->getMock('Bolt\Storage\Database\Schema\Manager', ['checkTablesIntegrity'], [$this->getApp()]);
        $check->expects($this->atLeastOnce())
            ->method('checkTablesIntegrity')
            ->will($this->returnValue($checkResponse));

        $this->setService('schema', $check);
        $this->setRequest(Request::create('/bolt/dbcheck'));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/dbcheck/dbcheck.twig');

        $this->controller()->check();
    }

    public function testUpdate()
    {
        $this->allowLogin($this->getApp());
        $checkResponse = new \Bolt\Storage\Database\Schema\CheckResponse();
        $check = $this->getMock('Bolt\Storage\Database\Schema\Manager', ['repairTables'], [$this->getApp()]);

        $check->expects($this->any())
            ->method('repairTables')
            ->will($this->returnValue($checkResponse));

        $this->setService('schema', $check);
        ResourceManager::$theApp = $this->getApp();

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST', ['return' => 'edit']));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST', ['return' => 'edit']));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST'));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result', $response->getTargetUrl());
    }

    public function testUpdateResult()
    {
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/dbupdate_result'));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/dbcheck/dbcheck.twig');

        $this->controller()->updateResult($this->getRequest());
    }

    /**
     * @return \Bolt\Controller\Backend\Database
     */
    protected function controller()
    {
        return $this->getService('controller.backend.database');
    }
}
