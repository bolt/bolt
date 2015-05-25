<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\CronServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/CronServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CronServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new CronServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Cron', $app['cron']);
        $app->boot();
    }
}
