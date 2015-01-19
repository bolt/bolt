<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\FilePermissionsServiceProvider;

/**
 * Class to test src/Provider/FilePermissionsServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class FilePermissionsServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new FilePermissionsServiceProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\FilePermissions', $app['filepermissions']);
        $app->boot();
    }
 
   
}