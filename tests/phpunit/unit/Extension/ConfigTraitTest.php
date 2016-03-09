<?php

namespace Bolt\Tests\Extension;

use Bolt\Filesystem\Handler\Directory;
use Bolt\Filesystem\Handler\YamlFile;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\ConfigExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\ConfigTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigTraitTest extends BoltUnitTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->resetDirs();

        $app = $this->getApp();
        $filesystem = $app['filesystem'];
        $filesystem->createDir('extensions://local/bolt/config/config');
        $filesystem->createDir('config://extensions');
        $file = new YamlFile();
        $filesystem->getFile('extensions://local/bolt/config/config/config.yml.dist', $file);
        $file->dump(['blame' => 'drop bear']);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->resetDirs();
    }

    private function resetDirs()
    {
        $app = $this->getApp();
        $filesystem = $app['filesystem'];
        if ($filesystem->has('extensions://local/bolt/config/config')) {
            $filesystem->deleteDir('extensions://local/bolt/config/config');
        }
        if ($filesystem->has('config://extensions')) {
            $filesystem->deleteDir('config://extensions');
        }
    }

    public function testDefaultConfigNoOverride()
    {
        $app = $this->getApp();

        $ext = new NormalExtension();
        $ext->setContainer($app);
        $refObj = new \ReflectionObject($ext);
        $method = $refObj->getMethod('getDefaultConfig');
        $method->setAccessible(true);

        $this->assertSame([], $method->invoke($ext));
    }

    public function testDefaultConfig()
    {
        $app = $this->getApp();

        $ext = new ConfigExtension();
        $ext->setContainer($app);
        $refObj = new \ReflectionObject($ext);
        $method = $refObj->getMethod('getDefaultConfig');
        $method->setAccessible(true);

        $this->assertSame(['blame' => 'koala'], $method->invoke($ext));
    }

    public function testInstallConfigFile()
    {
        $app = $this->getApp();
        $ext = new ConfigExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/config');
        $ext->setBaseDirectory($baseDir);
        $webDir = $app['filesystem']->getDir('extensions://');
        $ext->setWebDirectory($webDir);

        $refObj = new \ReflectionObject($ext);
        $method = $refObj->getMethod('getConfig');
        $method->setAccessible(true);

        $ext->setContainer($app);

        $conf = $method->invoke($ext);
        $this->assertSame(['blame' => 'drop bear'], $conf);

        // Second call should match
        $conf = $method->invoke($ext);
        $this->assertSame(['blame' => 'drop bear'], $conf);
    }

    public function testInstallConfigFileFailure()
    {
        $app = $this->getApp();
        $ext = new ConfigExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/config');
        $ext->setBaseDirectory($baseDir);
        $webDir = $app['filesystem']->getDir('extensions://');
        $ext->setWebDirectory($webDir);

        $refObj = new \ReflectionObject($ext);
        $method = $refObj->getMethod('getConfig');
        $method->setAccessible(true);

        $ext->setContainer($app);
        $ext->register($app);
        $filesystem = $app['filesystem'];
        $filesystem->delete('extensions://local/bolt/config/config/config.yml.dist');

        $conf = $method->invoke($ext);
        $this->assertSame(['blame' => 'koala'], $conf);
    }

    public function testConfigFileLocalOverRide()
    {
        $app = $this->getApp();
        $ext = new ConfigExtension();
        $baseDir = $app['filesystem']->getDir('extensions://');
        $baseDir->setPath('local/bolt/config');
        $ext->setBaseDirectory($baseDir);
        $webDir = $app['filesystem']->getDir('extensions://');
        $ext->setWebDirectory($webDir);

        $refObj = new \ReflectionObject($ext);
        $method = $refObj->getMethod('getConfig');
        $method->setAccessible(true);

        $ext->setContainer($app);
        $filesystem = $app['filesystem'];
        $file = new YamlFile();
        $filesystem->getFile('config://extensions/config.bolt_local.yml', $file);
        $file->dump(['blame' => 'gnomes']);

        $conf = $method->invoke($ext);
        $this->assertSame(['blame' => 'gnomes'], $conf);
    }

    public function testConfigFileInvalidYaml()
    {
        $app = $this->getApp();
        $filesystem = $app['filesystem'];
        $file = new YamlFile();
        $filesystem->getFile('config://extensions/config.bolt.yml', $file);
        $file->put("\tever so slightly invalid yaml");

        $ext = new ConfigExtension();
        $dir = $app['filesystem']->getDir('extensions://');
        $dir->setPath('local/bolt/config');
        $ext->setBaseDirectory($dir);

        $refObj = new \ReflectionObject($ext);
        $method = $refObj->getMethod('getConfig');
        $method->setAccessible(true);

        $ext->setContainer($app);

        $this->setExpectedException('Bolt\Filesystem\Exception\ParseException', 'A YAML file cannot contain tabs as indentation');

        $conf = $method->invoke($ext);
        $this->assertSame(['blame' => 'gnomes'], $conf);
    }
}
