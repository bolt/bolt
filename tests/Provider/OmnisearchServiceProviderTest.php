<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\OmnisearchServiceProvider;

/**
 * Class to test src/Provider/OmnisearchServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class OmnisearchServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new OmnisearchServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Omnisearch', $app['omnisearch']);
        $app->boot();
    }
 
   
}