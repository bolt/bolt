<?php

namespace Bolt\Tests;

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
        $this->assertInstanceOf('Bolt\\Filesystem\\Manager', $bolt['filesystem']);
    }

    public function testDefaultManagers()
    {
        $bolt = $this->getApp();
        $this->assertInstanceOf('Bolt\Filesystem\Filesystem', $bolt['filesystem']->getFilesystem('root'));
        $this->assertInstanceOf('Bolt\Filesystem\Filesystem', $bolt['filesystem']->getFilesystem('config'));
    }
}
