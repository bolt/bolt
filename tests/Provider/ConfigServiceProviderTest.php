<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\ConfigServiceProvider;

/**
 * Class to test src/Provider/ConfigServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ConfigServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new ConfigServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Config', $app['config']);
        $app->boot();
    }
 
   
}