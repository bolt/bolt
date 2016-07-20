<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\CronServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/CronServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CronServiceProviderTest extends BoltFunctionalTestCase
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
