<?php
namespace Bolt\Tests;

use Bolt\Configuration as Config;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

/**
 * Class to test correct operation of Filesystem Service Provider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FilesystemProviderTest extends BoltUnitTest
{
    public function testAppRegistries()
    {
        $config = new Config\ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $bolt = $this->getApp();

        $this->assertNotNull($bolt['filesystem']);
        $this->assertInstanceOf('Bolt\\Filesystem\\Manager', $bolt['filesystem']);
    }

    public function testDefaultManagers()
    {
        $config = new Config\ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $bolt = $this->getApp();
        $this->assertInstanceOf('Bolt\Filesystem\Filesystem', $bolt['filesystem']->getFilesystem('root'));
        $this->assertInstanceOf('Bolt\Filesystem\Filesystem', $bolt['filesystem']->getFilesystem('config'));
    }
}
