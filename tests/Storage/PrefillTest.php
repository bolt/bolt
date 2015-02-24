<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\Response;

/**
 * Class to test src/Storage/Prefill.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class PrefillTest extends BoltUnitTest
{

    public function testUrl()
    {
        $app = $this->getApp();
        $factory = new RequestFactory;
        $request = $factory->create('GET', '/');
        $response = new Response('200');
        $guzzle = $this->getMock('Guzzle\Service\Client', array('get', 'send'));
        $request->setClient($guzzle);
        $guzzle->expects($this->once())
            ->method('get')
            ->with('http://loripsum.net/api/1/veryshort')
            ->will($this->returnValue($request));

        $guzzle->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $app['guzzle.client'] = $guzzle;
        $app['prefill']->get('/1/veryshort');
    }
}
