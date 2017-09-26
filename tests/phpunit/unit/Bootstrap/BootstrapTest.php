<?php

namespace Bolt\Tests\Bootstrap;

use Bolt\Application as BoltApplication;
use Bolt\Bootstrap;
use Bolt\Tests\Extension\Mock\NormalExtension;
use PHPUnit\Framework\TestCase;
use Silex\Application as SilexApplication;
use Silex\HttpCache;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Http\RememberMe\ResponseListener;
use Symfony\Component\Yaml\Dumper;

/**
 * @covers \Bolt\Bootstrap
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BootstrapTest extends TestCase
{
    protected $rootPath = PHPUNIT_WEBROOT;

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->rootPath . '/.bolt.yml');
        $fs->remove($this->rootPath . '/.bolt.php');
    }

    public function testDefaultRun()
    {
        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(SilexApplication::class, $app);
        $this->assertInstanceOf(BoltApplication::class, $app);
        $this->assertAttributeEquals(true, 'initialized', $app);
        $this->assertAttributeEquals(false, 'booted', $app);
    }

    public function providerDefaultRunPaths()
    {
        return [
            [PHPUNIT_WEBROOT, '%root%'],
            [TEST_ROOT, '%bolt%'],
            [PHPUNIT_WEBROOT, '%site%'],
            [PHPUNIT_WEBROOT . '/app', '%app%'],
            [PHPUNIT_WEBROOT . '/app/cache', '%cache%'],
            [PHPUNIT_WEBROOT . '/app/config', '%config%'],
            [PHPUNIT_WEBROOT . '/app/database', '%database%'],
            [PHPUNIT_WEBROOT . '/extensions', '%extensions%'],
            [PHPUNIT_WEBROOT . '/app/config/extensions', '%extensions_config%'],
            [PHPUNIT_WEBROOT . '/var', '%var%'],
            [PHPUNIT_WEBROOT . '/public', '%web%'],
            [PHPUNIT_WEBROOT . '/public/files', '%files%'],
            [PHPUNIT_WEBROOT . '/public/theme', '%themes%'],
            [PHPUNIT_WEBROOT . '/public/bolt-public', '%bolt_assets%'],
        ];
    }

    /**
     * @dataProvider providerDefaultRunPaths
     */
    public function testDefaultRunPaths($expected, $path)
    {
        $app = Bootstrap::run($this->rootPath);
        $resolver = $app['path_resolver'];

        $this->assertSame($expected, $resolver->resolve($path));
    }

    public function providerRunPaths()
    {
        $ts = PHPUNIT_WEBROOT . '/test_site';

        return [
            [PHPUNIT_WEBROOT, '%root%'],
            [TEST_ROOT, '%bolt%'],
            [$ts, '%site%'],
            [$ts . '/test_app', '%app%'],
            [$ts . '/test_var/test_cache', '%cache%'],
            [$ts . '/test_app/test_config', '%config%'],
            [$ts . '/test_app/test_database', '%database%'],
            [$ts . '/test_extensions', '%extensions%'],
            [$ts . '/test_app/test_config/ext', '%extensions_config%'],
            [$ts . '/test_var', '%var%'],
            [$ts . '/test_web', '%web%'],
            [$ts . '/test_web/test_files', '%files%'],
            [$ts . '/test_web/test_themes', '%themes%'],
            [$ts . '/test_web/bank_vault', '%bolt_assets%'],
        ];
    }

    /**
     * @dataProvider providerRunPaths
     */
    public function testRunPathsYaml($expected, $path)
    {
        $config = [
            'paths' => [
                'root'              => 'test_root',
                'bolt'              => 'test_bolt',
                'site'              => 'test_site',
                'app'               => '%site%/test_app',
                'cache'             => '%var%/test_cache',
                'config'            => '%app%/test_config',
                'database'          => '%app%/test_database',
                'extensions'        => '%site%/test_extensions',
                'extensions_config' => '%app%/test_config/ext',
                'var'               => '%site%/test_var',
                'web'               => '%site%/test_web',
                'files'             => '%web%/test_files',
                'themes'            => '%web%/test_themes',
                'bolt_assets'       => '%web%/bank_vault',
            ],
        ];
        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);
        $resolver = $app['path_resolver'];

        $this->assertSame($expected, $resolver->resolve($path));
    }

    /**
     * @dataProvider providerRunPaths
     */
    public function testRunPathsPhp($expected, $path)
    {
        $config = <<<EOF
<?php
return [
    'paths' => [
        'root'              => 'test_root',
        'bolt'              => 'test_bolt',
        'site'              => 'test_site',
        'app'               => '%site%/test_app',
        'cache'             => '%var%/test_cache',
        'config'            => '%app%/test_config',
        'database'          => '%app%/test_database',
        'extensions'        => '%site%/test_extensions',
        'extensions_config' => '%app%/test_config/ext',
        'var'               => '%site%/test_var',
        'web'               => '%site%/test_web',
        'files'             => '%web%/test_files',
        'themes'            => '%web%/test_themes',
        'bolt_assets'       => '%web%/bank_vault',
    ]
];
EOF;
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.php', $config);

        $app = Bootstrap::run($this->rootPath);
        $resolver = $app['path_resolver'];

        $this->assertSame($expected, $resolver->resolve($path));
    }

    /**
     * @group legacy
     */
    public function testRunResourceManagerYaml()
    {
        $config = [
            'resources' => 'Bolt\Configuration\ResourceManager',
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);

        // We only care at this stage of the game that no exceptions are thrown
        $this->assertAttributeEquals(true, 'initialized', $app);
        $this->assertAttributeEquals(false, 'booted', $app);
    }

    /**
     * @group legacy
     */
    public function testRunResourceManagerPhp()
    {
        $config = <<<EOF
<?php
\$container = new \Silex\Application([
    'pathmanager' => new \Eloquent\Pathogen\FileSystem\Factory\FileSystemPathFactory(),
    'rootpath'    => '$this->rootPath',
]);

return [
    'resources' => new \Bolt\Configuration\ResourceManager(\$container),
];
EOF;
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.php', $config);

        $app = Bootstrap::run($this->rootPath);

        // We only care at this stage of the game that no exceptions are thrown
        $this->assertAttributeEquals(true, 'initialized', $app);
        $this->assertAttributeEquals(false, 'booted', $app);
    }

    public function testRunBootstrappedApplicationClass()
    {
        $config = <<<EOF
<?php
return new \Silex\Application();
EOF;
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.php', $config);

        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(SilexApplication::class, $app);
    }

    public function testRunCustomApplicationClass()
    {
        $config = [
            'application' => 'Silex\Application',
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(SilexApplication::class, $app);
    }

    public function testRunCustomApplicationObject()
    {
        $config = <<<EOF
<?php
namespace Koala;

class DropBear extends \Silex\Application {}

return [
    'application' => new \Koala\DropBear(),
];
EOF;
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.php', $config);

        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(SilexApplication::class, $app);
        $this->assertInstanceOf('Koala\DropBear', $app);
    }

    public function testRunService()
    {
        $config = [
            'services' => 'Silex\Provider\RememberMeServiceProvider',
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(ResponseListener::class, $app['security.remember_me.response_listener']);
    }

    public function testRunServices()
    {
        $config = [
            'services' => [
                'Silex\Provider\RememberMeServiceProvider',
                'Silex\Provider\HttpCacheServiceProvider',
            ],
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(ResponseListener::class, $app['security.remember_me.response_listener']);
        $this->assertInstanceOf(HttpCache::class, $app['http_cache']);
    }

    public function testRunServicesWithOptions()
    {
        $config = [
            'services' => [
                'Silex\Provider\RememberMeServiceProvider',
                [
                    'Silex\Provider\HttpCacheServiceProvider' => [
                        'http_cache.options' => ['http_cache.cache_dir' => $this->rootPath],
                    ],
                ],
            ],
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);

        $this->assertInstanceOf(ResponseListener::class, $app['security.remember_me.response_listener']);
        $this->assertInstanceOf(HttpCache::class, $app['http_cache']);
        $options = $app['http_cache.options'];
        $this->assertInternalType('array', $options);
        $this->assertArrayHasKey('http_cache.cache_dir', $options);
        $this->assertSame($this->rootPath, $options['http_cache.cache_dir']);
    }

    public function testRunExtensionString()
    {
        $config = [
            'extensions' => [
                'Bolt\Tests\Extension\Mock\NormalExtension',
            ],
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);
        $extensions = $app['extensions'];

        $ext = $extensions->get('Bolt/Normal');
        $this->assertInstanceOf(NormalExtension::class, $ext);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Extension class "stdClass" must implement Bolt\Extension\ExtensionInterface
     */
    public function testRunExtensionStringInvalid()
    {
        $config = [
            'extensions' => [
                'stdClass',
            ],
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);
        $extensions = $app['extensions'];

        $ext = $extensions->get('Bolt/Normal');
        $this->assertInstanceOf(NormalExtension::class, $ext);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Extension class name "DropBear\Ninja" is defined in .bolt.yml or .bolt.php, but the class name is misspelled or not loadable by Composer.
     */
    public function testRunExtensionStringNotFound()
    {
        $config = [
            'extensions' => [
                'DropBear\Ninja',
            ],
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);
        $extensions = $app['extensions'];

        $ext = $extensions->get('Bolt/Normal');
        $this->assertInstanceOf(NormalExtension::class, $ext);
    }

    public function testRunExtensionObject()
    {
        $config = <<<EOF
<?php

return [
    'extensions' => [
        new \Bolt\Tests\Extension\Mock\NormalExtension(),
    ],
];
EOF;
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.php', $config);

        $app = Bootstrap::run($this->rootPath);
        $extensions = $app['extensions'];

        $ext = $extensions->get('Bolt/Normal');
        $this->assertInstanceOf(NormalExtension::class, $ext);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Extension class "stdClass" must be an instance of Bolt\Extension\ExtensionInterface
     */
    public function testRunExtensionObjectInvalid()
    {
        $config = <<<EOF
<?php

return [
    'extensions' => [
        new \stdClass(),
    ],
];
EOF;
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.php', $config);

        $app = Bootstrap::run($this->rootPath);
        $extensions = $app['extensions'];

        $ext = $extensions->get('Bolt/Normal');
        $this->assertInstanceOf(NormalExtension::class, $ext);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Provided application object does not contain an extension service, but extensions are defined in bootstrap.
     */
    public function testRunExtensionInvalidCustomApplication()
    {
        $config = [
            'application' => 'Silex\Application',
            'extensions'  => [
                'stdClass',
            ],
        ];

        $yaml = (new Dumper())->dump($config, 4, 0, true);
        $fs = new Filesystem();
        $fs->dumpFile($this->rootPath . '/.bolt.yml', $yaml);

        $app = Bootstrap::run($this->rootPath);
        $extensions = $app['extensions'];

        $ext = $extensions->get('Bolt/Normal');
        $this->assertInstanceOf(NormalExtension::class, $ext);
    }
}
