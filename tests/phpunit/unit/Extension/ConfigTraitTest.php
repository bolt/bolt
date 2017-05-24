<?php

namespace Bolt\Tests\Extension;

use Bolt\Filesystem\Adapter\Memory;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\YamlFile;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\ConfigExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\ConfigTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigTraitTest extends BoltUnitTest
{
    /** @var DirectoryInterface */
    private $extDir;
    /** @var DirectoryInterface */
    private $extConfigDir;

    protected function setUp()
    {
        $this->extDir = (new Filesystem(new Memory()))->getDir('/');
        $this->extConfigDir = (new Filesystem(new Memory()))->getDir('/');

        /** @var YamlFile $file */
        $file = $this->extDir->getFile('config/config.yml.dist', new YamlFile());
        $file->dump(['blame' => 'drop bear']);
    }

    protected function makeApp()
    {
        $app = parent::makeApp();

        $app['filesystem']->mountFilesystem('extensions_config', $this->extConfigDir->getFilesystem());

        return $app;
    }

    public function testDefaultConfigNoOverride()
    {
        $ext = new NormalExtension();

        $result = $this->invoke($ext, 'getDefaultConfig');

        $this->assertSame([], $result);
    }

    public function testDefaultConfig()
    {
        $ext = $this->createExt();

        $result = $this->invoke($ext, 'getDefaultConfig');

        $this->assertSame(['blame' => 'koala'], $result);
    }

    public function testInstallConfigFile()
    {
        $ext = $this->createExt();

        $conf = $this->invoke($ext, 'getConfig');
        $this->assertSame(['blame' => 'drop bear'], $conf);

        // Second call should match
        $conf = $this->invoke($ext, 'getConfig');
        $this->assertSame(['blame' => 'drop bear'], $conf);
    }

    public function testInstallConfigFileFailure()
    {
        $this->extDir->getFile('config/config.yml.dist')->delete();

        $conf = $this->invoke($this->createExt(), 'getConfig');

        $this->assertSame(['blame' => 'koala'], $conf);
    }

    public function testConfigFileLocalOverRide()
    {
        /** @var YamlFile $file */
        $file = $this->extConfigDir->getFile('config.bolt_local.yml');
        $file->dump(['blame' => 'gnomes']);

        $conf = $this->invoke($this->createExt(), 'getConfig');

        $this->assertSame(['blame' => 'gnomes'], $conf);
    }

    /**
     * @expectedException \Bolt\Filesystem\Exception\ParseException
     * @expectedExceptionMessage A YAML file cannot contain tabs as indentation
     */
    public function testConfigFileInvalidYaml()
    {
        $this->extConfigDir->getFile('config.bolt.yml')->put("\tever so slightly invalid yaml");

        $this->invoke($this->createExt(), 'getConfig');
    }

    protected function createExt()
    {
        $ext = new ConfigExtension();
        $ext->setContainer($this->getApp());
        $ext->setBaseDirectory($this->extDir);
        $ext->register($this->getApp());

        return $ext;
    }

    protected function invoke($object, $method, array $args = [])
    {
        $refObj = new \ReflectionObject($object);
        $method = $refObj->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
