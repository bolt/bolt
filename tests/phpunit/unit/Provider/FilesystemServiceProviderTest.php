<?php

namespace Bolt\Tests\Provider;

use Bolt\Filesystem;
use Bolt\Provider\FilesystemServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test Bolt\Provider\FilesystemServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();

        $app->register(new FilesystemServiceProvider());
        $this->assertInstanceOf(Filesystem\Manager::class, $app['filesystem']);

        $app->boot();
    }
}
