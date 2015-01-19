<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\StorageServiceProvider;

/**
 * Class to test src/Provider/StorageServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StorageServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new StorageServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Storage', $app['storage']);
        $app->boot();
    }
 
   
}