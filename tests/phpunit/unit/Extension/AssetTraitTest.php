<?php

namespace Bolt\Tests\Extension;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Widget\Widget;
use Bolt\Filesystem\Adapter\Memory;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Directory;
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
    public function testEmptyQueues()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());
    }

    public function testRegisterAssetsNoOverride()
    {
        $app = $this->getApp(false);
        $ext = new NormalExtension();

        $app['extensions']->add($ext);
        $app->boot();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());
    }

    public function testEmptyRegisterAssets()
    {
        $app = $this->getApp(false);

        $ext = new AssetExtension();
        $ext->setAssets(null);

        $app['extensions']->add($ext);
        $app->boot();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());
        $this->assertSame([], $app['asset.queue.snippet']->getQueue());
        $this->assertSame([], $app['asset.queue.widget']->getQueue());
    }

    public function testRegisterValidAssetsNoPath()
    {
        $app = $this->getApp(false);

        $webDir = new Directory($app['filesystem']->getFilesystem('extensions'));
        $ext = new AssetExtension();
        $ext->setWebDirectory($webDir);
        $ext->setAssets(
            [
                JavaScript::create('test.js')->setPackageName('extensions'),
                new Snippet(),
                Stylesheet::create('test.css')->setPackageName('extensions'),
                new Widget(),
            ]
        );
        $ext->setContainer($app);
        $ext->setBaseDirectory($app['filesystem']->getDir('extensions://'));
        $ext->register($app);

        $app['extensions']->add($ext);
        $app->boot();

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
        $app = $this->getApp(false);
        $filesystem = $app['filesystem'];
        $filesystems = [
            'web' => new Filesystem(new Memory()),
        ];
        $filesystem->mountFilesystems($filesystems);
        $filesystem->put('web://extensions/local/bolt/koala/js/test.js', '');

        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript('js/test.js')]);
        $app['extensions']->add($ext, null, $filesystem->getDir('web://extensions/local/bolt/koala'));

        $app->boot();
        $fileQueue = $app['asset.queue.file']->getQueue();

        /** @var JavaScript $queued */
        $queued = reset($fileQueue['javascript']);
        $this->assertInstanceOf(JavaScript::class, $queued);
        $this->assertSame('extensions/local/bolt/koala/js/test.js', $queued->getFileName());
        $this->assertSame('extensions', $queued->getPackageName());
    }

    public function testRegisterValidAssetsThemePath()
    {
        $app = $this->getApp(false);
        $filesystem = $app['filesystem'];
        $filesystems = [
            'theme' => new Filesystem(new Memory()),
            'web' => new Filesystem(new Memory()),
        ];
        $filesystem->mountFilesystems($filesystems);
        $filesystem->getFilesystem('theme')->put('js/test.js', 'koala');

        $ext = new AssetExtension();
        $app['extensions']->add($ext, null, $filesystem->getDir('web://extensions/local/bolt/koala'));
        $ext->setAssets([new JavaScript('js/test.js')]);
        $ext->setContainer($app);
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
        $app = $this->getApp(false);

        $ext = new AssetExtension();
        $ext->setAssets('Turning our nightlights on in the daytime to scare');

        $app['extensions']->add($ext);
        $app->boot();

        $app['asset.queue.file']->getQueue();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Extension file assets must have a path set.
     */
    public function testRegisterInvalidPathAssets()
    {
        $app = $this->getApp(false);

        $ext = new AssetExtension();
        $ext->setAssets([new JavaScript()]);

        $app['extensions']->add($ext);
        $app->boot();

        $app['asset.queue.file']->getQueue();
    }
}
