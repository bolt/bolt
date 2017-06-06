<?php
namespace Bolt\Tests\Composer\Satis;

use Bolt\Composer\Satis\QueryService;
use Bolt\Tests\BoltUnitTest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Class to test src/Composer/Satis/QueryService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryServiceTest extends BoltUnitTest
{
    public function testPackageInfoValid()
    {
        $app = $this->getApp();
        $mock = new MockHandler([
            new Response(200, [], json_encode(['package' => 'gawain/clippy', 'version' => '2.3.4'])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $service = new QueryService($client, $app['extend.site'], $app['extend.urls']);

        $response = $service->info('gawain/clippy', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
    }

    public function testPackageInfoInvalid()
    {
        $app = $this->getApp();
        $mock = new MockHandler([
            new Response(200, [], json_encode(['package' => false, 'version' => false])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $service = new QueryService($client, $app['extend.site'], $app['extend.urls']);

        $response = $service->info('rossriley/mytest', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
        $this->assertFalse($response->package);
        $this->assertFalse($response->version);
    }

    public function testInfoList()
    {
        $app = $this->getApp();
        $mock = new MockHandler([
            new Response(200, [], json_encode(['packages' => [
                ['gawain/clippy', 'version' => '2.3.4'],
                ['evil/clippy', 'version' => '0.0.1'],
            ]])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $service = new QueryService($client, $app['extend.site'], $app['extend.urls']);

        $response = $service->all();
        $this->assertObjectHasAttribute('packages', $response);
        $this->assertNotCount(0, $response->packages);
    }
}
