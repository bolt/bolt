<?php

namespace Bolt\Tests\Provider;

use Bolt\Configuration\PathResolver;
use Bolt\Provider\PathServiceProvider;
use Bolt\Tests\BoltUnitTest;

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

        $this->assertSame('', $app['path_resolver.root']);
        $this->assertSame([], $app['path_resolver.paths']);
    }
}
