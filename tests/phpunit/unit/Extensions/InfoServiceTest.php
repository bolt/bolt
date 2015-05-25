<?php
namespace Bolt\Tests\Extensions;

use Bolt\Extensions\ExtensionsInfoService;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Extensions/ExtensionsInfoService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class InfoServiceTest extends BoltUnitTest
{
    public function testPackageInfoValid()
    {
        $app = $this->getApp();
        $service = new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);

        $response = $service->info('gawain/clippy', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
    }

    public function testPackageInfoInvalid()
    {
        $app = $this->getApp();
        $service = new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);

        $response = $service->info('rossriley/mytest', '2.0.0');
        $this->assertObjectHasAttribute('package', $response);
        $this->assertObjectHasAttribute('version', $response);
        $this->assertFalse($response->package);
        $this->assertFalse($response->version);
    }

    public function testInfoList()
    {
        $app = $this->getApp();
        $service = new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);

        $response = $service->all();
        $this->assertObjectHasAttribute('packages', $response);
        $this->assertNotCount(0, $response->packages);
    }
}
