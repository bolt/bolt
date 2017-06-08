<?php

namespace Bolt\Tests\Extension;

use Bolt\Filesystem\Adapter\Memory;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\TwigExtension;
use Bolt\Twig\FilesystemLoader;
use Twig\Loader\ArrayLoader;

/**
 * Class to test Bolt\Extension\TwigTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TwigTraitTest extends BoltUnitTest
{
    /** @var DirectoryInterface */
    private $baseDir;

    protected function setUp()
    {
        $fs = new Filesystem(new Memory());
        $this->baseDir = $fs->getDir('vendor/unit/test');
    }

    public function testTwigExtension()
    {
        $app = $this->getApp(false);
        $ext = new TwigExtension();
        $ext->setBaseDirectory($this->baseDir);
        $ext->setContainer($app);
        $ext->register($app);
        $app->boot();

        $this->addToAssertionCount(1);
    }

    public function testRenderTemplate()
    {
        $app = $this->getApp();
        $template = <<< TWIG
Function koala {{ koala(name) }}
Function dropbear {{ dropbear(name) }}
Filter koala {{ name|koala }}
Filter dropbear {{ name|dropbear }}
TWIG;
        $loader = new ArrayLoader(['marsupial.twig' => $template]);
        $app['twig']->setLoader($loader);

        $ext = new TwigExtension();
        $ext->setBaseDirectory($this->baseDir);
        $ext->setContainer($app);
        $ext->register($app);

        $context = ['name' => 'Kenny Koala'];
        $html = $ext->getTestTemplateOutput('marsupial.twig', $context);

        $this->assertRegExp('/Function koala kenny koala/', $html);
        $this->assertRegExp('/Function dropbear kenny koala/', $html);
        $this->assertRegExp('/Filter koala KENNY KOALA/', $html);
        $this->assertRegExp('/Filter dropbear KENNY KOALA/', $html);
    }

    public function testPathAddition()
    {
        $app = $this->getApp();
        $ext = new TwigExtension();

        $ext->setBaseDirectory($this->baseDir);
        $ext->setContainer($app);
        $ext->register($app);
        $app->boot();

        $dropbear = $this->baseDir->getDir('dropbear');
        $koala = $this->baseDir->getDir('koala');
        $dropbear->create();
        $koala->create();

        $boltLoaderMock = $this->getMockBuilder(FilesystemLoader::class)
            ->disableOriginalConstructor()
            ->setMethods(['prependDir', 'addDir'])
            ->getMock()
        ;
        $boltLoaderMock
            ->expects($this->atLeastOnce())
            ->method('prependDir')
            ->with($dropbear, 'Marsupial')
        ;
        $boltLoaderMock
            ->expects($this->atLeastOnce())
            ->method('addDir')
            ->with($koala)
        ;
        $this->setService('twig.loader.bolt_filesystem', $boltLoaderMock);

        $app['twig']->getExtensions();
    }
}
