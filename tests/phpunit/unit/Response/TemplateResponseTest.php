<?php

namespace Bolt\Tests\Response;

use Bolt\Collection\Bag;
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
        $template = 'error.twig';
        $context = ['foo' => 'bar'];

        $response = new TemplateResponse($template, $context);
        $this->assertResponse($response, $template, $context);

        $response = TemplateResponse::create($template, $context);
        $this->assertResponse($response, $template, $context);
    }

    /**
     * @param TemplateResponse $response
     * @param string           $template
     * @param array            $context
     * @param int              $status
     */
    protected function assertResponse($response, $template, $context, $status = 200)
    {
        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($template, $response->getTemplate());
        $this->assertInstanceOf(Bag::class, $response->getContext());
        $this->assertEquals($context, $response->getContext()->toArray());
    }
}
