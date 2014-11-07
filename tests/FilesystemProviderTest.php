<?php
namespace Bolt\Tests;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Bolt\Configuration as Config;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

/**
 * Class to test correct operation of Filesystem Service Provider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class FilesystemProviderTest extends \PHPUnit_Framework_TestCase
{

    public function setup()
    {

    }

    public function tearDown()
    {

    }

    public function testAppRegistries()
    {
        $config = new Config\ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->compat();
        $bolt = $this->getApp();

        $this->assertNotNull($bolt['filesystem']);
        $this->assertInstanceOf('Bolt\\Filesystem\\Manager', $bolt['filesystem']);
    }

    public function testDefaultManagers()
    {
        $config = new Config\ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->compat();
        $bolt = $this->getApp();
        $this->assertInstanceOf('League\Flysystem\Filesystem', $bolt['filesystem']->getManager());
        $this->assertInstanceOf('League\Flysystem\Filesystem', $bolt['filesystem']->getManager('config'));
    }

    protected function getApp()
    {
        $sessionMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->setMethods(array('clear'))
            ->setConstructorArgs(array(new MockFileSessionStorage()))
            ->getMock();

        $config = new Config\ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $bolt = new Application(array('resources' => $config));

        $bolt['config']->set(
            'general/database',
            array(
                'driver' => 'sqlite',
                'databasename' => 'test',
                'username' => 'test',
                'memory' => true
            )
        );

        $bolt['session'] = $sessionMock;
        $bolt->initialize();

        return $bolt;
    }
}
