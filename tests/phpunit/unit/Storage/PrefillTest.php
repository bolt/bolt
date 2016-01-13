<?php

namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test src/Storage/Prefill.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PrefillTest extends BoltUnitTest
{
    public function testUrl()
    {
        $app = $this->getApp();

        $response = new Response(Response::HTTP_OK);

        if ($app['guzzle.api_version'] === 5) {
            $factory = new MessageFactory();
            $request = $factory->createRequest('GET', '/');
        } else {
            $request = new Request('GET', '/');
        }

        $guzzle = $this->getMock('GuzzleHttp\Client', ['get']);

        $guzzle->expects($this->once())
            ->method('get')
            ->with('http://loripsum.net/api/1/veryshort')
            ->will($this->returnValue($request));

        $app['guzzle.client'] = $guzzle;
        $app['prefill']->get('/1/veryshort');
    }
}
