<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\CronServiceProvider;

/**
 * Class to test src/Provider/CronServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class CronServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new CronServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Controllers\Cron', $app['cron']);
        $app->boot();
    }
 
   
}