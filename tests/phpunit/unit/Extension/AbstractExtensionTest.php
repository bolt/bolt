<?php

namespace Bolt\Tests\Extension;

use Bolt\Filesystem\Handler\Directory;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\BasicExtension;
use Bolt\Tests\Extension\Mock\Extension;

/**
 * Class to test Bolt\Extension\AbstractExtension
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AbstractExtensionTest extends BoltUnitTest
{
    public function testClassProperties()
    {
        $this->assertClassHasAttribute('container', 'Bolt\Extension\AbstractExtension');
        $this->assertClassHasAttribute('baseDirectory', 'Bolt\Extension\AbstractExtension');
        $this->assertClassHasAttribute('relativeUrl', 'Bolt\Extension\AbstractExtension');
        $this->assertClassHasAttribute('name', 'Bolt\Extension\AbstractExtension');
        $this->assertClassHasAttribute('vendor', 'Bolt\Extension\AbstractExtension');
        $this->assertClassHasAttribute('namespace', 'Bolt\Extension\AbstractExtension');
    }

    public function testContainer()
    {
        $ext = new BasicExtension();
        $ext->setContainer($this->getApp());

        $this->assertInstanceOf('Silex\Application', $ext->getContainer());
    }

    public function testBaseDirectory()
    {
        $dir = new Directory();
        $dir->setPath(__DIR__);
        $ext = new BasicExtension();

        $this->assertInstanceOf('Bolt\Extension\AbstractExtension', $ext->setBaseDirectory($dir));
        $this->assertInstanceOf('Bolt\Filesystem\Handler\Directory', $ext->getBaseDirectory());
        $this->assertSame(__DIR__, $ext->getBaseDirectory()->getPath());
    }

    public function testRelativeUrl()
    {
        $ext = new BasicExtension();

        $this->assertInstanceOf('Bolt\Extension\AbstractExtension', $ext->setRelativeUrl('/koalas'));
        $this->assertSame('/koalas', $ext->getRelativeUrl());
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
