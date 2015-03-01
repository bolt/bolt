<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\FilesystemProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/FilesystemProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
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
