<?php

namespace Bolt\Tests\Provider;

use Bolt\Provider\PathServiceProvider;
use Bolt\Tests\BoltUnitTest;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

/**
 * Class to test src/Provider/PathServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PathServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new PathServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(PlatformFileSystemPathFactory::class, $app['pathmanager']);
        $app->boot();
    }
}
