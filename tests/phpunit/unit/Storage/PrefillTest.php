<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use GuzzleHttp\Message\MessageFactory;
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

        $factory = new MessageFactory;
        $request = $factory->createRequest('GET', '/');
        $response = new Response(Response::HTTP_OK);
        $guzzle = $this->getMock('GuzzleHttp\Client', ['get']);

        $guzzle->expects($this->once())
            ->method('get')
            ->with('http://loripsum.net/api/1/veryshort')
            ->will($this->returnValue($request));

        $app['guzzle.client'] = $guzzle;
        $app['prefill']->get('/1/veryshort');
    }
}
