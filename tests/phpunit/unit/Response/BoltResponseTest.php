<?php

namespace Bolt\Tests\Response;

use Bolt\Response\BoltResponse;
use Bolt\Tests\BoltUnitTest;

class BoltResponseTest extends BoltUnitTest
{
    public function testCreate()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), $this->getContext());

        $this->assertInstanceOf('Bolt\Response\BoltResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $context = $response->getContext();
        $this->assertEquals('1555', $context['context']['code']);
    }

    public function testToString()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), $this->getContext());
        $this->assertRegExp('#Bolt - Fatal error.#', (string) $response);
    }

    public function testSetTemplate()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), $this->getContext());
        $newTwig = $app['twig']->loadTemplate('error.twig');
        $response->setTemplate($newTwig);
        $this->assertSame($newTwig, $response->getTemplate());
    }

    public function testSetContext()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), []);
        $response->setContext(['test' => 'tester']);
        $this->assertEquals(['test' => 'tester'], $response->getContext());
    }

    public function testGlobalContext()
    {
        $app = $this->getApp();

        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), [], $this->getContext());

        $globalContext = $response->getGlobalContext();
        $this->assertEquals('1555', $globalContext['context']['code']);
    }

    public function testGetTemplateName()
    {
        $app = $this->getApp();
        $response = BoltResponse::create($app['twig']->loadTemplate('error.twig'), []);
        $this->assertEquals('error.twig', $response->getTemplateName());
    }

    protected function getContext()
    {
        return ['context' => [
            'class'   => 'BoltResponse',
            'message' => 'Clippy is bent out of shape',
            'code'    => '1555',
            'trace'   => [],
        ]];
    }
}
