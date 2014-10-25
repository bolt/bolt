<?php
namespace Bolt\Tests;

use Bolt\Application;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Composer;
use Symfony\Component\HttpFoundation\Request;
use Eloquent\Pathogen\FileSystem\PlatformFileSystemPath;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

/**
 * Class to test correct operation and locations of resource manager class and extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ResourceManagerTest extends \PHPUnit_Framework_TestCase
{

    public function setup()
    {

    }

    public function tearDown()
    {
        @unlink(TEST_ROOT . '/app/cache/config_cache.php');
    }

    public function testConstruction()
    {
        $container = new \Pimple(
            array(
                'rootpath' => TEST_ROOT,
                'pathmanager' => new PlatformFileSystemPathFactory()
            )
        );
        $config = new ResourceManager($container);
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT), \PHPUnit_Framework_Assert::readAttribute($config, 'root'));
    }

    public function testDefaultPaths()
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT), $config->getPath('rootpath'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/app'), $config->getPath('apppath'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/extensions'), $config->getPath('extensions'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/files'), $config->getPath('filespath'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/.'), $config->getPath('web'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/app/cache'), $config->getPath('cache'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/app/config'), $config->getPath('config'));
     }

    /**
     * @dataProvider exceptionGetPathProvider
     * @expectedException InvalidArgumentException
     */
    public function testExceptionGetPath($path)
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->getPath($path);
    }

    public function exceptionGetPathProvider()
    {
        return array(
            array(
                ''
            ),
            array(
                'FAKE_PATH'
            )
        );
    }

    public function testShortAliasedPaths()
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT), $config->getPath('root'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/app'), $config->getPath('app'));
        $this->assertEquals(PlatformFileSystemPath::fromString(TEST_ROOT . '/files'), $config->getPath('files'));
    }

    public function testDefaultUrls()
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $this->assertEquals('/', $config->getUrl('root'));
        $this->assertEquals('/app/', $config->getUrl('app'));
        $this->assertEquals('/extensions/', $config->getUrl('extensions'));
        $this->assertEquals('/async/', $config->getUrl('async'));
        $this->assertEquals('/bolt/', $config->getUrl('bolt'));
        $this->assertEquals('/files/', $config->getUrl('files'));
    }

    /**
     * @dataProvider exceptionGetUrlProvider
     * @expectedException InvalidArgumentException
     */
    public function testExceptionGetUrl($url)
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->getUrl($url);
    }

    public function exceptionGetUrlProvider()
    {
        return array(
            array(
                ''
            ),
            array(
                'FAKE_URL'
            )
        );
    }

    public function testBoltAppSetup()
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->compat();

        $app = new Application(array('resources' => $config));
        $this->assertEquals($config->getPaths(), $app['resources']->getPaths());

        // Test that the Application has initialised the resources, injecting in config values.
        $this->assertContains(PlatformFileSystemPath::fromString(TEST_ROOT . '/theme')->string(), $config->getPath('theme'));
        $this->assertNotEmpty($config->getUrl('canonical'));
    }

    public function testDefaultRequest()
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $app = new Application(array('resources' => $config));
        $this->assertEquals('cli', $config->getRequest('protocol'));
        $this->assertEquals('bolt.dev', $config->getRequest('hostname'));
        $this->assertEquals('cli://bolt.dev/bolt', $config->getUrl('canonical'));
        $this->assertEquals('cli://bolt.dev', $config->getUrl('host'));
        $this->assertEquals('cli://bolt.dev/', $config->getUrl('rooturl'));
        $this->assertEquals('cli://bolt.dev/', $config->getUrl('rooturl'));
    }

    /**
     * @dataProvider exceptionGetRequest
     * @expectedException InvalidArgumentException
     */
    public function testExceptionGetRequest($request)
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->getRequest($request);
    }

    public function exceptionGetRequest()
    {
        return array(
            array(
                ''
            ),
            array(
                'FAKE_REQUEST'
            )
        );
    }

    public function testCustomRequest()
    {
        $request = Request::create(
            '/bolt/test/location',
            'GET',
            array(),
            array(),
            array(),
            array(
                'HTTP_HOST' => 'test.dev',
                'SERVER_PROTOCOL' => 'https'
            )
        );
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'request' => $request,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $config->compat();
        $app = new Application(array('resources' => $config));
        $this->assertEquals('https', $config->getRequest('protocol'));
        $this->assertEquals('test.dev', $config->getRequest('hostname'));
        $this->assertEquals('https://bolt.dev/bolt/test/location', $config->getUrl('canonical'));
    }

    public function testComposerCustomConfig()
    {
        $config = new Composer(TEST_ROOT);
        $config->compat();
        $app = new Application(array('resources' => $config));
        $this->assertEquals('/bolt-public/', $config->getUrl('app'));
    }

    public function testNonRootDirectory()
    {
        $request = Request::create(
            '/sub/directory/bolt/test/location',
            'GET',
            array(),
            array(),
            array(),
            array(
                'SCRIPT_NAME' => '/sub/directory/index.php',
                'PHP_SELF' => '/sub/directory/index.php',
                'SCRIPT_FILENAME' => '/path/to/sub/directory/index.php'
            )
        );

        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'request' => $request,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $app = new Application(array('resources' => $config));
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
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $app = new Application(array('resources' => $config));
        $this->assertEquals('/bolt/', $config->getUrl('bolt'));
        $this->assertEquals('/bolt/files/files/', $app['config']->get('general/wysiwyg/filebrowser/imageBrowseUrl'));
    }

    public function testConfigDrivenUrlsWithBrandingOverride()
    {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $app = new Application(array('resources' => $config));
        $app['config']->set('general/branding/path', '/custom');
        $config->initialize();
        $this->assertEquals('/custom/', $config->getUrl('bolt'));
        $this->assertEquals('/custom/files/files/', $app['config']->get('general/wysiwyg/filebrowser/imageBrowseUrl'));
    }

    public function testConfigsWithNonRootDirectory()
    {
        $request = Request::create(
            '/sub/directory/bolt/test/location',
            'GET',
            array(),
            array(),
            array(),
            array(
                'SCRIPT_NAME' => '/sub/directory/index.php',
                'PHP_SELF' => '/sub/directory/index.php',
                'SCRIPT_FILENAME' => '/path/to/sub/directory/index.php'
            )
        );

        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'request' => $request,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );
        $app = new Application(array('resources' => $config));
        $app['config']->set('general/branding/path', '/custom');
        $config->initialize();
        $this->assertEquals('/sub/directory/custom/', $config->getUrl('bolt'));
        $this->assertEquals(
            '/sub/directory/custom/files/files/',
            $app['config']->get('general/wysiwyg/filebrowser/imageBrowseUrl')
        );
    }

    public function testFindRelativePath() {
        $config = new ResourceManager(
            new \Pimple(
                array(
                    'rootpath' => TEST_ROOT,
                    'pathmanager' => new PlatformFileSystemPathFactory()
                )
            )
        );

        $rel = $config->findRelativePath(TEST_ROOT, TEST_ROOT . '/A/B');
        $this->assertEquals('A/B/', $rel);
    }
}
