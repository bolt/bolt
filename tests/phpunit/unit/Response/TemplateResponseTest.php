<?php

namespace Bolt\Tests\Response;

use Bolt\Response\TemplateResponse;
use Bolt\Tests\BoltUnitTest;

/**
 * TemplateResponse test.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TemplateResponseTest extends BoltUnitTest
{
    public function testCreate()
    {
        $response = $this->createTestResponse('error.twig', $this->getContext(), []);

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $context = $response->getContext();
        $this->assertEquals('1555', $context['context']['code']);
    }

    public function testToString()
    {
        $response = $this->createTestResponse('error.twig', $this->getContext(), []);

        $this->assertRegExp('#Bolt - Fatal error.#', (string) $response);
    }

    public function testSetTemplate()
    {
        $app = $this->getApp();
        $response = $this->createTestResponse('error.twig', [], []);
        $newTwig = $app['twig']->loadTemplate('error.twig');
        $response->setTemplate($newTwig);

        $this->assertSame($newTwig, $response->getTemplate());
    }

    public function testSetContext()
    {
        $response = $this->createTestResponse('error.twig', [], []);
        $response->setContext(['test' => 'tester']);

        $this->assertEquals(['test' => 'tester'], $response->getContext());
    }

    public function testGlobalContext()
    {
        $response = $this->createTestResponse('error.twig', [], $this->getContext());

        $globalContext = $response->getGlobals();
        $this->assertEquals('1555', $globalContext['context']['code']);
    }

    public function testGetTemplateName()
    {
        $response = $this->createTestResponse('error.twig', [], []);

        $this->assertEquals('error.twig', $response->getTemplate()->getTemplateName());
    }

    /**
     * @param string $templateName
     * @param array  $context
     * @param array  $globals
     *
     * @return TemplateResponse
     */
    protected function createTestResponse($templateName, $context, $globals)
    {
        $app = $this->getApp();
        $template = $app['twig']->loadTemplate($templateName);

        $response = new TemplateResponse($template->render($context));
        $response
            ->setTemplate($template)
            ->setContext($context)
            ->setGlobals($globals)
        ;

        return $response;
    }

    protected function getContext()
    {
        return [
            'context' => [
                'class'   => 'TemplateResponse',
                'message' => 'Clippy is bent out of shape',
                'code'    => '1555',
                'trace'   => [],
            ],
        ];
    }
}
