<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Database\Schema\SchemaCheck;
use Bolt\Tests\Controller\ControllerUnitTest;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
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
     * @covers \Bolt\Storage\Database\Schema\SchemaCheck
     */
    public function testCheck()
    {
        $this->allowLogin($this->getApp());
        $checkResponse = new SchemaCheck();
        $check = $this->getMockSchemaManager(['check']);
        $check->expects($this->atLeastOnce())
            ->method('check')
            ->will($this->returnValue($checkResponse));

        $this->setService('schema', $check);
        $this->setRequest(Request::create('/bolt/dbcheck'));

        $response = $this->controller()->check($this->getRequest());
        $this->assertEquals('@bolt/dbcheck/dbcheck.twig', $response->getTemplate());
    }

    public function testUpdate()
    {
        $this->allowLogin($this->getApp());
        $checkResponse = new SchemaCheck();
        $check = $this->getMockSchemaManager(['update']);

        $check->expects($this->any())
            ->method('update')
            ->will($this->returnValue($checkResponse));

        $this->setService('schema', $check);

        $this->setRequest(Request::create('/bolt/dbupdate', 'POST'));
        $response = $this->controller()->update($this->getRequest());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result', $response->getTargetUrl());
    }

    public function testUpdateResult()
    {
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/dbupdate_result'));

        $response = $this->controller()->updateResult();
        $this->assertEquals('@bolt/dbcheck/dbcheck.twig', $response->getTemplate());
    }

    /**
     * @return \Bolt\Controller\Backend\Database
     */
    protected function controller()
    {
        return $this->getService('controller.backend.database');
    }

    /**
     * @param array $methods
     *
     * @return Manager|MockObject
     */
    protected function getMockSchemaManager(array $methods)
    {
        return $this->getMockBuilder(Manager::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
        ;
    }
}
