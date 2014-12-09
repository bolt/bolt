<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\PermissionsServiceProvider;

/**
 * Class to test src/Provider/PermissionsServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class PermissionsServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new PermissionsServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Permissions', $app['permissions']);
        $app->boot();
    }
 
   
}