<?php

namespace Bolt\Tests\Provider;

use Bolt\Tests\BoltUnitTest;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

/**
 * @covers \Bolt\Provider\PathServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PathServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(PlatformFileSystemPathFactory::class, $app['pathmanager']);
    }
}
