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
        $this->baseDir = (new Filesystem(new Memory()))->getDir('/');
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
        $app = $this->getApp(false);
        $template = <<< TWIG
Function koala {{ koala(name) }}
Function dropbear {{ dropbear(name) }}
Filter koala {{ name|koala }}
Filter dropbear {{ name|dropbear }}
TWIG;

        $ext = new TwigExtension();
        $ext->setBaseDirectory($this->baseDir);
        $ext->setContainer($app);
        $ext->register($app);
        $app->boot();

        $loader = new ArrayLoader(['marsupial.twig' => $template]);
        $app['twig']->setLoader($loader);

        $context = ['name' => 'Kenny Koala'];
        $html = $ext->getTestTemplateOutput('marsupial.twig', $context);

        $this->assertRegExp('/Function koala kenny koala/', $html);
        $this->assertRegExp('/Function dropbear kenny koala/', $html);
        $this->assertRegExp('/Filter koala KENNY KOALA/', $html);
        $this->assertRegExp('/Filter dropbear KENNY KOALA/', $html);
    }

    public function testPathAddition()
    {
        $dropbear = $this->baseDir->getDir('dropbear');
        $koala = $this->baseDir->getDir('koala');
        $dropbear->create();
        $koala->create();

        $app = $this->getApp(false);
        $filesystem = $app['filesystem'];

        $ext = new TwigExtension();
        $app['extensions']->add($ext, $this->baseDir);

        $boltLoaderMock = $this->getMockBuilder(FilesystemLoader::class)
            ->disableOriginalConstructor()
            ->setMethods(['addDir', 'prependPath'])
            ->getMock()
        ;
        $boltLoaderMock
            ->expects($this->any())
            ->method('prependPath')
            ->with(
                $this->callback(
                    function($subject){
                        /** @var \Bolt\Filesystem\Handler\Directory $subject */
                        return $subject->getPath() === 'dropbear';
                    }
                ),
                'Marsupial'
            )
        ;
        $boltLoaderMock
            ->expects($this->any())
            ->method('addDir')
            ->with(
                $this->callback(
                    function($subject){
                        /** @var \Bolt\Filesystem\Handler\Directory $subject */
                        return $subject->getPath() === 'koala';
                    }
                ),
                '__main__'
            )
        ;
        $this->setService('twig.loader.bolt_filesystem', $boltLoaderMock);
        $filesystems = [
            'theme' => new Filesystem(new Memory()),
        ];
        $filesystem->mountFilesystems($filesystems);

        $app['twig']->getExtensions();
        $this->addToAssertionCount(2);
    }
}
