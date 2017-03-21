<?php

namespace Bolt\Tests\Extension;

use Bolt\Extension\AbstractExtension;
use Bolt\Filesystem\Handler\Directory;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\BasicExtension;
use Bolt\Tests\Extension\Mock\Extension;
use Silex\Application;

/**
 * Class to test Bolt\Extension\AbstractExtension
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AbstractExtensionTest extends BoltUnitTest
{
    public function testClassProperties()
    {
        $this->assertClassHasAttribute('container', AbstractExtension::class);
        $this->assertClassHasAttribute('baseDirectory', AbstractExtension::class);
        $this->assertClassHasAttribute('webDirectory', AbstractExtension::class);
        $this->assertClassHasAttribute('name', AbstractExtension::class);
        $this->assertClassHasAttribute('vendor', AbstractExtension::class);
        $this->assertClassHasAttribute('namespace', AbstractExtension::class);
    }

    public function testContainer()
    {
        $ext = new BasicExtension();
        $ext->setContainer($this->getApp());

        $this->assertInstanceOf(Application::class, $ext->getContainer());
    }

    public function testBaseDirectory()
    {
        $app = $this->getApp();
        $webDir = $app['filesystem']->getDir('extensions://');
        $dir = new Directory();
        $dir->setPath(__DIR__);
        $ext = new BasicExtension();
        $ext->setWebDirectory($webDir);

        $this->assertInstanceOf(AbstractExtension::class, $ext->setBaseDirectory($dir));
        $this->assertInstanceOf(Directory::class, $ext->getBaseDirectory());
        $this->assertSame(__DIR__, $ext->getBaseDirectory()->getPath());
    }

    public function testRelativeUrl()
    {
        $app = $this->getApp();
        $webDir = new Directory($app['filesystem']->getFilesystem('extensions'));
        $ext = new BasicExtension();
        $ext->setWebDirectory($webDir);

        $this->assertInstanceOf(Directory::class, $ext->getWebDirectory());
    }

    public function testGetId()
    {
        $ext = new BasicExtension();

        $this->assertSame('Bolt/Basic', $ext->getId());
    }

    public function testGetName()
    {
        $ext = new BasicExtension();

        $this->assertSame('Basic', $ext->getName());
    }

    public function testGetNameLegacy()
    {
        $ext = new Extension();

        $this->assertSame('Mock', $ext->getName());
    }

    public function testGetVendor()
    {
        $ext = new BasicExtension();

        $this->assertSame('Bolt', $ext->getVendor());
    }

    public function testGetNamespace()
    {
        $ext = new BasicExtension();

        $this->assertSame('Bolt\Tests\Extension\Mock', $ext->getNamespace());
    }
}
