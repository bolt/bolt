<?php

namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use GuzzleHttp\Psr7\Request;

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
        $request = new Request('GET', '/');

        $guzzle = $this->getMockGuzzleClient();
        $guzzle->expects($this->once())
            ->method('get')
            ->with('http://loripsum.net/api/1/veryshort')
            ->will($this->returnValue($request));

        $this->setService('guzzle.client', $guzzle);
        $app['prefill']->get('/1/veryshort');
    }
}
