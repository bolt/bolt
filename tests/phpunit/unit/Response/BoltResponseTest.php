<?php

namespace Bolt\Tests\Response;

use Bolt\Response\BoltResponse;
use Bolt\Tests\BoltUnitTest;

class BoltResponseTest extends BoltUnitTest
{
    public function testCreate()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array('foo' => 'bar'));

        $this->assertInstanceOf('Bolt\Response\BoltResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $context = $response->getContext();
        $this->assertEquals('bar', $context['foo']);
    }

    public function testToString()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array('foo' => 'bar'));
        $this->assertRegexp("#Bolt - Fatal error.#", (string)$response);
    }
    
    public function testSetRenderer()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array('foo' => 'bar'));
        $newTwig = $app['twig']->loadTemplate('error.twig');
        $response->setRenderer($newTwig);
        $this->assertSame($newTwig, $response->getRenderer());
    }
    
    public function testSetContext()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array());
        $response->setContext(array('test' => 'tester'));
        $this->assertEquals(array('test' => 'tester'), $response->getContext());
    }
    
    public function testGetGlobalContext()
    {
        $app = $this->getApp();
        $app['twig']->addGlobal('foo', 'test');

        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array());
        $globalContext = $response->getGlobalContext();
        $this->assertEquals('test', $globalContext['foo']);
    }
    
    public function testGetTemplate()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array());
        $this->assertEquals('error.twig', $response->getTemplate());
    }
}
