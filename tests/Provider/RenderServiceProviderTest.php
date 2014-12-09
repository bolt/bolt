<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\RenderServiceProvider;

/**
 * Class to test src/Provider/RenderServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class RenderServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new RenderServiceProvider($app, false);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Render', $app['render']);
        $app->boot();
    }
    
    public function testSafeProvider()
    {
        $app = $this->getApp(); 
        $provider = new RenderServiceProvider($app, true);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Render', $app['safe_render']);
        $app->boot();
    }
 
   
}