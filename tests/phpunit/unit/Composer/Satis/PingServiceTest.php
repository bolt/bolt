<?php

namespace Bolt\Tests\Composer\Satis;

use Bolt\Composer\Satis\PingService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \Bolt\Composer\Satis\PingService
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PingServiceTest extends TestCase
{
    public function testPing()
    {
        $response = new Psr7\Response();
        $response->withStatus(200);
        $mock = new MockHandler([$response]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $requestStack = $this->createMock(RequestStack::class);

        $pingService = new PingService($client, $requestStack, 'http://example.com/');
        $pingService->ping();
        $messages = $pingService->getMessages();

        $this->assertEmpty($messages);
    }

    public function providerSetupPingExceptions()
    {
        $request = new Psr7\Request('GET', 'https://example.com');

        return [
            [new MockHandler([new ClientException('There was a 400', $request)]), '/^Client error: There was a 400/'],
            [new MockHandler([new ServerException('There was a 500', $request)]), '/^Extension server returned an error: There was a 500/'],
            [new MockHandler([new RequestException('DNS down', $request)]), '/^Testing connection to extension server failed: DNS down/'],
            [new MockHandler([new Exception('Drop bear')]), '/^Generic failure while testing connection to extension server: Drop bear/'],
        ];
    }

    /**
     * @dataProvider providerSetupPingExceptions
     *
     * @param MockHandler $mock
     * @param string      $regex
     */
    public function testSetupPingExceptions($mock, $regex)
    {
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $requestStack = $this->createMock(RequestStack::class);

        $pingService = new PingService($client, $requestStack, 'http://example.com/');
        $pingService->ping();
        $messages = $pingService->getMessages();

        $this->assertRegExp($regex, $messages[0]);
    }

    /**
     * @deprecated remove in v4 as PHPUnit 5 includes this
     */
    protected function createMock($originalClassName)
    {
        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock()
        ;
    }
}
