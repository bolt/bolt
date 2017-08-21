<?php

namespace Bolt\Tests\Composer\Satis;

use Bolt\Common\Json;
use Bolt\Composer\Satis\QueryService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Composer\Satis\QueryService
 *
 * @group slow
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryServiceTest extends TestCase
{
    public function testPackageInfoValid()
    {
        $mock = new MockHandler([
            new Response(200, [], Json::dump(['package' => 'gawain/clippy', 'version' => '2.3.4'])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $service = new QueryService($client, 'https://market.bolt.cm/', ['list' => 'list.json', 'info' => 'info.json']);

        $response = $service->info('gawain/clippy', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
    }

    public function testPackageInfoInvalid()
    {
        $mock = new MockHandler([
            new Response(200, [], Json::dump(['package' => false, 'version' => false])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $service = new QueryService($client, 'https://market.bolt.cm/', ['list' => 'list.json', 'info' => 'info.json']);

        $response = $service->info('rossriley/mytest', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
        $this->assertFalse($response->package);
        $this->assertFalse($response->version);
    }

    public function testInfoList()
    {
        $mock = new MockHandler([
            new Response(200, [], Json::dump(['packages' => [
                ['gawain/clippy', 'version' => '2.3.4'],
                ['evil/clippy', 'version' => '0.0.1'],
            ]])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $service = new QueryService($client, 'https://market.bolt.cm/', ['list' => 'list.json', 'info' => 'info.json']);

        $response = $service->all();
        $this->assertObjectHasAttribute('packages', $response);
        $this->assertNotCount(0, $response->packages);
    }
}
