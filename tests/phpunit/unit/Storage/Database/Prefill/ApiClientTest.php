<?php

namespace Bolt\Tests\Storage\Database\Prefill;

use Bolt\Storage\Database\Prefill\ApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Bolt\Storage\Database\Prefill\ApiClient
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ApiClientTest extends TestCase
{
    public function testGet()
    {
        $expected = '<p>Ecce aliud simile dissimile. </p>';
        $guzzleMock = $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock()
        ;
        $guzzleMock
            ->expects($this->once())
            ->method('get')
            ->with('http://loripsum.net/api/1/veryshort')
            ->willReturn(new Response(22, [], $expected))
        ;
        $apiClient = new ApiClient($guzzleMock);

        $response = $apiClient->get('/1/veryshort');
        $this->assertSame($expected, $response->getContents());
    }
}
