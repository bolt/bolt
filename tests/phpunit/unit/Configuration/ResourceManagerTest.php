<?php

namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Standard;
use Bolt\Tests\BoltUnitTest;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Eloquent\Pathogen\FileSystem\PlatformFileSystemPath as Path;
use Eloquent\Pathogen\PathInterface;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation and locations of resource manager class and extensions.
 *
 * @group legacy
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ResourceManagerTest extends BoltUnitTest
{
    public function testConstruction()
    {
        $container = new \Pimple(
            [
                'rootpath'    => PHPUNIT_WEBROOT,
                'pathmanager' => new PlatformFileSystemPathFactory(),
            ]
        );
        $config = new ResourceManager($container);
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT), Assert::readAttribute($config, 'root'));
    }

    public function testDefaultPaths()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $config->setPathResolver($config->getPathResolverFactory()->create());

        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT), $config->getPath('rootpath'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/app'), $config->getPath('apppath'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/extensions'), $config->getPath('extensions'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/public/files'), $config->getPath('filespath'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/public'), $config->getPath('web'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/app/cache'), $config->getPath('cache'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/app/config'), $config->getPath('config'));
    }

    /**
     * @dataProvider exceptionGetPathProvider
     * @expectedException \InvalidArgumentException
     *
     * @param mixed $path
     */
    public function testExceptionGetPath($path)
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $config->getPath($path);
    }

    public function exceptionGetPathProvider()
    {
        return [
            [''],
            ['FAKE_PATH'],
            ['FAKE_PATH/test'],
        ];
    }

    public function testShortAliasedPaths()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $config->setPathResolver($config->getPathResolverFactory()->create());

        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT), $config->getPath('root'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT), $config->getPath('rootpath'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/app'), $config->getPath('app'));
        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/public/files'), $config->getPath('files'));
        $this->assertInstanceOf(PathInterface::class, $config->getPathObject('root'));
    }

    public function testRelativePathCreation()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $config->setPathResolver($config->getPathResolverFactory()->create());

        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/app/cache/test'), $config->getPath('cache/test'));
    }

    public function testDefaultUrls()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $app = new Application(['resources' => $config]);

        $this->assertEquals('/', $config->getUrl('root'));
        $this->assertEquals('/app/', $config->getUrl('app'));
        $this->assertEquals('/extensions/', $config->getUrl('extensions'));
        $this->assertEquals('/async/', $config->getUrl('async'));
        $this->assertEquals('/bolt/', $config->getUrl('bolt'));
        $this->assertEquals('/files/', $config->getUrl('files'));
    }

    /**
     * @dataProvider exceptionGetUrlProvider
     * @expectedException \InvalidArgumentException
     *
     * @param mixed $url
     */
    public function testExceptionGetUrl($url)
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $config->getUrl($url);
    }

    public function exceptionGetUrlProvider()
    {
        return [
            [''],
            ['FAKE_URL'],
        ];
    }

    public function testBoltAppSetup()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );

        $app = new Application(['resources' => $config]);
        $this->assertEquals($config->getPaths(), $app['resources']->getPaths());

        // Test that the Application has initialised the resources, injecting in config values.
        $this->assertContains(Path::fromString(PHPUNIT_WEBROOT . '/public/theme')->string(), $config->getPath('theme'));
        $this->assertNotEmpty($config->getUrl('canonical'));
    }

    public function testDefaultRequest()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $app = new Application(['resources' => $config]);
        $this->assertEquals('http', $config->getRequest('protocol'));
        $this->assertEquals('bolt.test', $config->getRequest('hostname'));
        $this->assertEquals('http://bolt.test/bolt', $config->getUrl('canonical'));
        $this->assertEquals('http://bolt.test', $config->getUrl('host'));
        $this->assertEquals('http://bolt.test/', $config->getUrl('rooturl'));
    }

    /**
     * @dataProvider exceptionGetRequest
     * @expectedException \InvalidArgumentException
     *
     * @param mixed $request
     */
    public function testExceptionGetRequest($request)
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $config->getRequest($request);
    }

    public function exceptionGetRequest()
    {
        return [
            [''],
            ['FAKE_REQUEST'],
        ];
    }

    public function testCustomRequest()
    {
        $request = Request::create(
            '/bolt/test/location',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST'       => 'test.test',
                'SERVER_PROTOCOL' => 'https',
            ]
        );
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'request'     => $request,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        new Application(['resources' => $config]);
        $this->assertEquals('https', $config->getRequest('protocol'));
        $this->assertEquals('test.test', $config->getRequest('hostname'));
    }

    public function testNonRootDirectory()
    {
        $request = Request::create(
            '/sub/directory/bolt/test/location',
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_NAME'     => '/sub/directory/index.php',
                'PHP_SELF'        => '/sub/directory/index.php',
                'SCRIPT_FILENAME' => '/path/to/sub/directory/index.php',
            ]
        );

        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'request'     => $request,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $app = new Application(['resources' => $config]);
        $this->assertEquals('/sub/directory/', $config->getUrl('root'));
        $this->assertEquals('/sub/directory/app/', $config->getUrl('app'));
        $this->assertEquals('/sub/directory/extensions/', $config->getUrl('extensions'));
        $this->assertEquals('/sub/directory/files/', $config->getUrl('files'));
        $this->assertEquals('/sub/directory/async/', $config->getUrl('async'));
        $this->assertContains('/sub/directory/theme/', $config->getUrl('theme'));
    }

    public function testConfigDrivenUrls()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $app = new Application(['resources' => $config]);
        $this->assertEquals('/bolt/', $config->getUrl('bolt'));
    }

    public function testConfigDrivenUrlsWithBrandingOverride()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $app = new Application(['resources' => $config]);
        $app['config']->set('general/branding/path', '/custom');
        $config->initialize();
        $this->assertEquals('/custom/', $config->getUrl('bolt'));
    }

    public function testConfigsWithNonRootDirectory()
    {
        $request = Request::create(
            '/sub/directory/bolt/test/location',
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_NAME'     => '/sub/directory/index.php',
                'PHP_SELF'        => '/sub/directory/index.php',
                'SCRIPT_FILENAME' => '/path/to/sub/directory/index.php',
            ]
        );

        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'request'     => $request,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );
        $app = new Application(['resources' => $config]);
        $app['config']->set('general/branding/path', '/custom');
        $config->initialize();
        $this->assertEquals('/sub/directory/custom/', $config->getUrl('bolt'));
    }

    public function testFindRelativePath()
    {
        $config = new ResourceManager(
            new \Pimple(
                [
                    'rootpath'    => PHPUNIT_WEBROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory(),
                ]
            )
        );

        $rel = $config->findRelativePath(PHPUNIT_WEBROOT, PHPUNIT_WEBROOT . '/A/B');
        $this->assertEquals('A/B/', $rel);
    }

    public function testSetThemePath()
    {
        $resources = new Standard(PHPUNIT_WEBROOT);
        $app = new Application(['resources' => $resources]);
        $app['config']->set('general/theme', 'test');
        $app['config']->set('general/theme_path', '/testpath');

        $this->assertEquals(Path::fromString(PHPUNIT_WEBROOT . '/testpath/test')->string(), $resources->getPath('theme'));
    }

    public function testStaticApp()
    {
        $config = new Standard(PHPUNIT_WEBROOT);
        $app = new Application(['resources' => $config]);
        $app2 = ResourceManager::getApp();
        $this->assertEquals($app, $app2);
    }
}
