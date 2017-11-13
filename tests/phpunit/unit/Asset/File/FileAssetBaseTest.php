<?php

namespace Bolt\Tests\Asset\File;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Asset\File\FileAssetBase
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileAssetBaseTest extends TestCase
{
    public function providerAssets()
    {
        return [
            [],
        ];
    }

    public function testEmptyConstructor()
    {
        $file = new JavaScript();
        self::assertNull($file->getPath());
        self::assertNull($file->getPackageName());

        $file = new Stylesheet();
        self::assertNull($file->getPath());
        self::assertNull($file->getPackageName());
    }

    public function testConstructor()
    {
        $file = new JavaScript('gum-tree', 'dropbear');
        self::assertSame('gum-tree', $file->getPath());
        self::assertSame('dropbear', $file->getPackageName());

        $file = new Stylesheet('gum-tree', 'dropbear');
        self::assertSame('gum-tree', $file->getPath());
        self::assertSame('dropbear', $file->getPackageName());
    }

    public function testEmptyCreate()
    {
        $file = JavaScript::create();
        self::assertNull($file->getPath());
        self::assertNull($file->getPackageName());

        $file = Stylesheet::create();
        self::assertNull($file->getPath());
        self::assertNull($file->getPackageName());
    }

    public function testCreate()
    {
        $file = JavaScript::create('gum-tree', 'dropbear');
        self::assertSame('gum-tree', $file->getPath());
        self::assertSame('dropbear', $file->getPackageName());

        $file = Stylesheet::create('gum-tree', 'dropbear');
        self::assertSame('gum-tree', $file->getPath());
        self::assertSame('dropbear', $file->getPackageName());
    }

    public function testType()
    {
        $file = JavaScript::create();
        self::assertSame('javascript', $file->getType());

        $file = Stylesheet::create();
        self::assertSame('stylesheet', $file->getType());
    }

    public function testFileName()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setFileName('dropbear'));
        self::assertSame('dropbear', $file->getFileName());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setFileName('koala'));
        self::assertSame('koala', $file->getFileName());
    }

    public function testPath()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setPath('dropbear'));
        self::assertSame('dropbear', $file->getPath());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setPath('koala'));
        self::assertSame('koala', $file->getPath());
    }

    public function testPackageName()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setPackageName('dropbear'));
        self::assertSame('dropbear', $file->getPackageName());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setPackageName('koala'));
        self::assertSame('koala', $file->getPackageName());
    }

    public function testUrl()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setUrl('dropbear'));
        self::assertSame('dropbear', $file->getUrl());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setUrl('koala'));
        self::assertSame('koala', $file->getUrl());
    }

    public function testLate()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setLate(true));
        self::assertTrue($file->isLate());
        self::assertInstanceOf(JavaScript::class, $file->setLate(false));
        self::assertFalse($file->isLate());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setLate(true));
        self::assertTrue($file->isLate());
        self::assertInstanceOf(Stylesheet::class, $file->setLate(false));
        self::assertFalse($file->isLate());
    }

    public function testPriority()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setPriority(42));
        self::assertSame(42, $file->getPriority());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setPriority(24));
        self::assertSame(24, $file->getPriority());
    }

    public function testLocation()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setLocation('dropbear'));
        self::assertSame('dropbear', $file->getLocation());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setLocation('koala'));
        self::assertSame('koala', $file->getLocation());
    }

    public function testAttributes()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setAttributes(['async', 'integrity=false']));
        self::assertSame('async integrity=false', $file->getAttributes());
        self::assertSame(['async', 'integrity=false'], $file->getAttributes(true));
    }

    public function testAddAttributes()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->addAttribute('async'));
        self::assertInstanceOf(JavaScript::class, $file->addAttribute('integrity=false'));
        self::assertSame('async integrity=false', $file->getAttributes());
        self::assertSame(['async', 'integrity=false'], $file->getAttributes(true));
    }

    public function testZone()
    {
        $file = JavaScript::create();
        self::assertInstanceOf(JavaScript::class, $file->setZone('dropbear'));
        self::assertSame('dropbear', $file->getZone());

        $file = Stylesheet::create();
        self::assertInstanceOf(Stylesheet::class, $file->setZone('koala'));
        self::assertSame('koala', $file->getZone());
    }

    public function testStringJavaScript()
    {
        $file = JavaScript::create();
        $file->setUrl('https://dropbear.com.au/danger.js');
        $file->setAttributes(['async', 'integrity=false']);

        self::assertSame('<script src="https://dropbear.com.au/danger.js" async integrity=false></script>', (string) $file);
    }

    public function testStringStyleSheet()
    {
        $file = Stylesheet::create();
        $file->setUrl('https://koala.com.au/gum-tree.css');

        self::assertSame('<link rel="stylesheet" href="https://koala.com.au/gum-tree.css" media="screen">', (string) $file);
    }
}
