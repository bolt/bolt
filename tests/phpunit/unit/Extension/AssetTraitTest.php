<?php

namespace Bolt\Tests\Extension;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Widget\Widget;
use Bolt\Filesystem\Adapter\Memory;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Directory;
use Bolt\Filesystem\Manager;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\AssetExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\AssetTrait.
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

        $this->assertInstanceOf(JavaScript::class, reset($fileQueue['javascript']));
        $this->assertInstanceOf(Stylesheet::class, reset($fileQueue['stylesheet']));
        $this->assertInstanceOf(Snippet::class, reset($snippetQueue));
        $this->assertInstanceOf(Widget::class, reset($widgetQueue));
    }

    public function testRegisterValidAssetsExtensionPath()
    {
        $app = $this->getApp();

        $filesystem = new Manager([
            'theme' => new Filesystem(new Memory()),
            'web'   => new Filesystem(new Memory()),
        ]);
        $this->setService('filesystem', $filesystem);

        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript('js/test.js')]);
        $ext->setContainer($app);
        $ext->setWebDirectory($filesystem->getDir('web://extensions/local/bolt/koala'));

        $ext->register($app);

        $filesystem->put('web://extensions/local/bolt/koala/js/test.js', '');

        $fileQueue = $app['asset.queue.file']->getQueue();

        /** @var JavaScript $queued */
        $queued = reset($fileQueue['javascript']);
        $this->assertInstanceOf(JavaScript::class, $queued);
        $this->assertSame('extensions/local/bolt/koala/js/test.js', $queued->getFileName());
        $this->assertSame('extensions', $queued->getPackageName());
    }

    public function testRegisterValidAssetsThemePath()
    {
        $app = $this->getApp();

        $filesystem = new Manager([
            'theme' => new Filesystem(new Memory()),
            'web'   => new Filesystem(new Memory()),
        ]);
        $this->setService('filesystem', $filesystem);

        $filesystem->put('theme://js/test.js', '');

        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript('js/test.js')]);
        $ext->setContainer($app);
        $ext->setWebDirectory($filesystem->getDir('web://bolt/koala'));

        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();

        /** @var JavaScript $queued */
        $queued = reset($fileQueue['javascript']);
        $this->assertInstanceOf(JavaScript::class, $queued);
        $this->assertSame('js/test.js', $queued->getFileName());
        $this->assertSame('theme', $queued->getPackageName());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bolt\Tests\Extension\Mock\AssetExtension::registerAssets() should return a list of Bolt\Asset\AssetInterface objects. Got: string
     */
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

        $app['asset.queue.file']->getQueue();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Extension file assets must have a path set.
     */
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

        $app['asset.queue.file']->getQueue();
    }
}
