<?php

namespace Bolt\Tests;

use Bolt\Filesystem\Manager;
use Bolt\Filesystem\Filesystem;

/**
 * Class to test correct operation of Filesystem Service Provider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FilesystemProviderTest extends BoltUnitTest
{
    public function testAppRegistries()
    {
        $bolt = $this->getApp();

        $this->assertNotNull($bolt['filesystem']);
        $this->assertInstanceOf(Manager::class, $bolt['filesystem']);
    }

    public function testDefaultManagers()
    {
        $bolt = $this->getApp();
        $this->assertInstanceOf(Filesystem::class, $bolt['filesystem']->getFilesystem('root'));
        $this->assertInstanceOf(Filesystem::class, $bolt['filesystem']->getFilesystem('config'));
    }
}
