<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\CacheServiceProvider;

/**
 * Class to test src/Provider/CacheServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class CacheServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new CacheServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Cache', $app['cache']);
        $app->boot();
    }
 
   
}