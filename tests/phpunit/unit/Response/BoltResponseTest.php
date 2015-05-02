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
    
    public function testSetTemplate()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array('foo' => 'bar'));
        $newTwig = $app['twig']->loadTemplate('error.twig');
        $response->setTemplate($newTwig);
        $this->assertSame($newTwig, $response->getTemplate());
    }
    
    public function testSetContext()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array());
        $response->setContext(array('test' => 'tester'));
        $this->assertEquals(array('test' => 'tester'), $response->getContext());
    }

    public function testGlobalContext()
    {
        $app = $this->getApp();

        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array(), array('foo' => 'test'));

        $globalContext = $response->getGlobalContext();
        $this->assertEquals('test', $globalContext['foo']);
    }
    
    public function testGetTemplateName()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), array());
        $this->assertEquals('error.twig', $response->getTemplateName());
    }
}
