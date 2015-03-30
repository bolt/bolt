<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\Response as V3Response;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Message\Response;

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

        if ($app['deprecated.php']) {
            $factory = new RequestFactory;
            $request = $factory->create('GET', '/');
            $response = new V3Response('200');
            $guzzle = $this->getMock('Guzzle\Service\Client', array('get', 'send'));
            $request->setClient($guzzle);

            $guzzle->expects($this->once())
                ->method('send')
                ->will($this->returnValue($response));
        } else {
            $factory = new MessageFactory;
            $request = $factory->createRequest('GET', '/');
            $response = new Response('200');
            $guzzle = $this->getMock('GuzzleHttp\Client', array('get'));
        }

        $guzzle->expects($this->once())
            ->method('get')
            ->with('http://loripsum.net/api/1/veryshort')
            ->will($this->returnValue($request));

        $app['guzzle.client'] = $guzzle;
        $app['prefill']->get('/1/veryshort');
    }
}
