<?php

namespace Bolt\Tests\Provider;

use Bolt\Cron;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\CronServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CronServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Cron::class, $app['cron']);
    }
}
