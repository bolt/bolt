<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\FilesystemProvider;

/**
 * Class to test src/Provider/FilesystemProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class FilesystemProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp(); 
        $provider = new FilesystemProvider($app);    
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Filesystem\Manager', $app['filesystem']);
        $app->boot();
    }
 
   
}