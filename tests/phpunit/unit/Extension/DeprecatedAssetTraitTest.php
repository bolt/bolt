<?php

namespace Bolt\Tests\Extension;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;
use Bolt\Filesystem\Handler\Directory;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\DeprecatedAssetExtension;

/**
 * Class to test Bolt\Extension\AssetTrait deprecated functions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DeprecatedAssetTraitTest extends BoltUnitTest
{
    public function testAddCssString()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addCss', ['test.css']);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();
        $queued = reset($fileQueue['stylesheet']);

        $this->assertInstanceOf('Bolt\Asset\File\Stylesheet', $queued);
        $this->assertSame('test.css', $queued->getFileName());
    }

    public function testAddCssObject()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addCss', [new Stylesheet('test.css')]);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();
        $queued = reset($fileQueue['stylesheet']);

        $this->assertInstanceOf('Bolt\Asset\File\Stylesheet', $queued);
        $this->assertSame('test.css', $queued->getFileName());
    }

    public function testAddJavascriptString()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addJavascript', ['test.js']);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();
        $queued = reset($fileQueue['javascript']);

        $this->assertInstanceOf('Bolt\Asset\File\JavaScript', $queued);
        $this->assertSame('test.js', $queued->getFileName());
    }

    public function testAddJavascriptObject()
    {
        $app = $this->getApp();

        $this->assertSame(['javascript' => [], 'stylesheet' => []], $app['asset.queue.file']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addJavascript', [new JavaScript('test.js')]);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $fileQueue = $app['asset.queue.file']->getQueue();
        $queued = reset($fileQueue['javascript']);

        $this->assertInstanceOf('Bolt\Asset\File\JavaScript', $queued);
        $this->assertSame('test.js', $queued->getFileName());
    }

    public function testAddSnippetString()
    {
        $app = $this->getApp();

        $this->assertSame([], $app['asset.queue.snippet']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addSnippet', [
            Zone::FRONTEND,
            'snippetCallback',
            5,
        ]);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $queue = $app['asset.queue.snippet']->getQueue();
        $queued = reset($queue);

        $this->assertInstanceOf('Bolt\Asset\Snippet\Snippet', $queued);
        $this->assertSame('Drop Bear casualties today: 5', (string) $queued);
    }

    public function testAddSnippetCallable()
    {
        $app = $this->getApp();

        $this->assertSame([], $app['asset.queue.snippet']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addSnippet', [
            Zone::FRONTEND,
            [$ext, 'snippetCallback'],
            42,
        ]);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $queue = $app['asset.queue.snippet']->getQueue();
        $queued = reset($queue);

        $this->assertInstanceOf('Bolt\Asset\Snippet\Snippet', $queued);
        $this->assertSame('Drop Bear casualties today: 42', (string) $queued);
    }

    public function testAddWidget()
    {
        $app = $this->getApp();

        $this->assertSame([], $app['asset.queue.widget']->getQueue());

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addWidget', [new Widget()]);
        $ext->setBaseDirectory(new Directory());
        $ext->setContainer($app);
        $ext->register($app);

        $queue = $app['asset.queue.widget']->getQueue();
        $queued = reset($queue);

        $this->assertInstanceOf('Bolt\Asset\Widget\Widget', $queued);
    }

    public function testAddJquery()
    {
        $app = $this->getApp();
        $app['config']->set('general/add_jquery', false);

        $this->assertFalse($app['config']->get('general/add_jquery'));

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('addJquery', []);
        $ext->setContainer($app);
        $ext->register($app);

        $this->assertTrue($app['config']->get('general/add_jquery'));
    }

    public function testDisableJquery()
    {
        $app = $this->getApp();
        $app['config']->set('general/add_jquery', true);

        $this->assertTrue($app['config']->get('general/add_jquery'));

        $ext = new DeprecatedAssetExtension();
        $ext->setRegisterFunction('disableJquery', []);
        $ext->setContainer($app);
        $ext->register($app);

        $this->assertFalse($app['config']->get('general/add_jquery'));
    }
}
