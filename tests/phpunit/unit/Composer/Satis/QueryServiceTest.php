<?php
namespace Bolt\Tests\Composer\Satis;

use Bolt\Composer\Satis\QueryService;
use Bolt\Tests\BoltUnitTest;

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
        $service = new QueryService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);

        $response = $service->info('gawain/clippy', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
    }

    public function testPackageInfoInvalid()
    {
        $app = $this->getApp();
        $service = new QueryService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);

        $response = $service->info('rossriley/mytest', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
        $this->assertFalse($response->package);
        $this->assertFalse($response->version);
    }

    public function testInfoList()
    {
        $app = $this->getApp();
        $service = new QueryService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);

        $response = $service->all();
        $this->assertObjectHasAttribute('packages', $response);
        $this->assertNotCount(0, $response->packages);
    }
}
