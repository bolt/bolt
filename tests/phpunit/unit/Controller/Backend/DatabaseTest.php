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
    public function testCheck()
    {
        $this->allowLogin($this->getApp());
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('checkTablesIntegrity'), array($this->getApp()));
        $check->expects($this->atLeastOnce())
            ->method('checkTablesIntegrity')
            ->will($this->returnValue(array('message', 'hint')));

        $this->setService('integritychecker', $check);
        $this->setRequest(Request::create('/bolt/dbcheck'));
        $this->checkTwigForTemplate($this->getApp(), 'dbcheck/dbcheck.twig');

        $this->controller()->check($this->getRequest());
    }

    public function testUpdate()
    {
        $this->allowLogin($this->getApp());
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('repairTables'), array($this->getApp()));

        $check->expects($this->at(0))
            ->method('repairTables')
            ->will($this->returnValue(''));

        $check->expects($this->at(1))
            ->method('repairTables')
            ->will($this->returnValue('Testing'));

        $this->setService('integritychecker', $check);
        ResourceManager::$theApp = $this->getApp();

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST', array('return' => 'edit')));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST', array('return' => 'edit')));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST'));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result?messages=null', $response->getTargetUrl());
    }

    public function testUpdateResult()
    {
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/dbupdate_result'));
        $this->checkTwigForTemplate($this->getApp(), 'dbcheck/dbcheck.twig');

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
