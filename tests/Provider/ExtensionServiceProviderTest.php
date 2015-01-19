<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\ExtensionServiceProvider;

/**
 * Class to test src/Provider/ExtensionServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ExtensionServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new ExtensionServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Extensions', $app['extensions']);
        $this->assertInstanceOf('Bolt\Extensions\StatService', $app['extensions.stats']);
        $app->boot();
    }
 
   
}