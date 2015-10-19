<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\ImageHandler;

/**
 * Class to test Bolt\Twig\Handler\ImageHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ImageHandlerTest extends BoltUnitTest
{
    public function testImageFileNameLegacy()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image('generic-logo.png');
        $this->assertSame('/files/generic-logo.png', $result);
    }

    public function testImageFileNameArray()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image(['filename' => 'generic-logo.png']);
        $this->assertSame('/files/generic-logo.png', $result);
    }

    public function testImageFileNameWidthOnly()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image(['filename' => 'generic-logo.png'], 20);
        $this->assertSame('/thumbs/20x120crop/generic-logo.png', $result);
    }

    public function testImageFileNameHeightOnly()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image(['filename' => 'generic-logo.png'], '', 20);
        $this->assertSame('/thumbs/160x20crop/generic-logo.png', $result);
    }

    public function testImageFileNameWidthHeight()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20);
        $this->assertSame('/thumbs/20x20c/generic-logo.png', $result);
    }

    public function testImageFileNameCrop()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'f');
        $this->assertSame('/thumbs/20x20f/generic-logo.png', $result);
        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'fit');
        $this->assertSame('/thumbs/20x20f/generic-logo.png', $result);

        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'r');
        $this->assertSame('/thumbs/20x20r/generic-logo.png', $result);
        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'resize');
        $this->assertSame('/thumbs/20x20r/generic-logo.png', $result);

        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'b');
        $this->assertSame('/thumbs/20x20b/generic-logo.png', $result);
        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'borders');
        $this->assertSame('/thumbs/20x20b/generic-logo.png', $result);

        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'c');
        $this->assertSame('/thumbs/20x20c/generic-logo.png', $result);
        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20, 'crop');
        $this->assertSame('/thumbs/20x20c/generic-logo.png', $result);
    }
}
