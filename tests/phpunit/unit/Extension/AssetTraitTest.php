<?php

namespace Bolt\Tests\Extension;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Widget\Widget;
use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Directory;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\AssetExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\AssetTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetTraitTest extends BoltUnitTest
{
    public function testRegisterAssetsNoOverride()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());

        $ext = new NormalExtension();
        $ext->setContainer($app);
        $ext->register($app);

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());
    }

    public function testEmptyRegisterAssets()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());

        $ext = new AssetExtension();
        $ext->setAssets(null);
        $ext->setContainer($app);
        $ext->register($app);

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());
    }

    public function testRegisterValidAssetsNoPath()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());

        $webDir = new Directory($app['filesystem']->getFilesystem('extensions'));
        $ext = new AssetExtension();
        $ext->setWebDirectory($webDir);
        $ext->setAssets(
            [
                new JavaScript('test.js'),
                new Snippet(),
                new Stylesheet('test.css'),
                new Widget(),
            ]
        );
        $ext->setContainer($app);
        $ext->setBaseDirectory($app['filesystem']->getDir('extensions://'));
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();
        $snippetQueue = $app['asset.queue.snippet']->getQueue();
        $widgetQueue = $app['asset.queue.widget']->getQueue();

        $this->assertInstanceOf('Bolt\Asset\File\JavaScript', reset($fileQueue['javascript']));
        $this->assertInstanceOf('Bolt\Asset\File\Stylesheet', reset($fileQueue['stylesheet']));
        $this->assertInstanceOf('Bolt\Asset\Snippet\Snippet', reset($snippetQueue));
        $this->assertInstanceOf('Bolt\Asset\Widget\Widget', reset($widgetQueue));
    }

    public function testRegisterValidAssetsExtensionPath()
    {
        $app = $this->getApp();

        $dir = $app['filesystem']->getDir('extensions://');
        $dir->setPath('local/bolt/koala');

        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript('test.js')]);
        $ext->setContainer($app);
        $ext->setBaseDirectory($dir);

        $mockFile = $this->getMock('\Bolt\Filesystem\Handler\File', ['exists', 'getPath']);
        $mockFile
            ->method('exists')
            ->willReturn(true)
        ;
        $mockFile
            ->method('getPath')
            ->willReturn('/extensions/local/bolt/koala/test.js')
        ;
        $mockDir = $this->getMock('\Bolt\Filesystem\Handler\Directory', ['getFile']);
        $mockDir
            ->method('getFile')
            ->willReturn($mockFile)
        ;
        $ext->setWebDirectory($mockDir);

        //$app['filesystem'] = $mock;
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();

        $queued = reset($fileQueue['javascript']);
        $this->assertInstanceOf('Bolt\Asset\File\JavaScript', $queued);
        $this->assertSame('/extensions/local/bolt/koala/test.js', $queued->getFileName());
    }

    public function testRegisterValidAssetsThemePath()
    {
        $app = $this->getApp();

        $mockParams = [
            'root'       => new Filesystem(new Local($app['resources']->getPath('root'))),
            'web'        => new Filesystem(new Local($app['resources']->getPath('web'))),
            'app'        => new Filesystem(new Local($app['resources']->getPath('app'))),
            'view'       => new Filesystem(new Local($app['resources']->getPath('view'))),
            'default'    => new Filesystem(new Local($app['resources']->getPath('files'))),
            'files'      => new Filesystem(new Local($app['resources']->getPath('files'))),
            'config'     => new Filesystem(new Local($app['resources']->getPath('config'))),
            'themes'     => new Filesystem(new Local($app['resources']->getPath('themebase'))),
            'theme'      => new Filesystem(new Local($app['resources']->getPath('themebase') . '/' . $app['config']->get('general/theme'))),
            'extensions' => new Filesystem(new Local($app['resources']->getPath('extensions'))),
            'cache'      => new Filesystem(new Local($app['resources']->getPath('cache'))),
        ];
        $mock = $this->getMock('\Bolt\Filesystem\Manager', ['has'], [$mockParams]);
        $mock->expects($this->any())
            ->method('has')
            ->willReturn(true)
        ;

        $dir = $app['filesystem']->getDir('extensions://');
        $dir->setPath('local/bolt/koala');

        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript('js/test.js')]);
        $ext->setContainer($app);
        $ext->setBaseDirectory($dir);

        $webDir = $app['filesystem']->getDir('extensions://');
        $ext->setWebDirectory($webDir);

        $app['filesystem'] = $mock;
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();

        $queued = reset($fileQueue['javascript']);
        $this->assertInstanceOf('Bolt\Asset\File\JavaScript', $queued);
        $this->assertSame('js/test.js', $queued->getFileName());
    }

    public function testRegisterInvalidAssets()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());

        $dir = $app['filesystem']->getDir('extensions://');
        $ext = new AssetExtension();
        $ext->setAssets('Turning our nightlights on in the daytime to scare');
        $ext->setContainer($app);
        $ext->setBaseDirectory($dir);
        $ext->register($app);

        $this->setExpectedException('InvalidArgumentException', 'Bolt\Tests\Extension\Mock\AssetExtension::registerAssets() should return a list of Bolt\Asset\AssetInterface objects. Got: string');
        $app['asset.queue.file']->getQueue();
    }

    public function testRegisterInvalidPathAssets()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());

        $dir = $app['filesystem']->getDir('extensions://');
        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript()]);
        $ext->setContainer($app);
        $ext->setBaseDirectory($dir);
        $ext->register($app);

        $this->setExpectedException('RuntimeException', 'Extension file assets must have a path set.');
        $app['asset.queue.file']->getQueue();
    }
}
