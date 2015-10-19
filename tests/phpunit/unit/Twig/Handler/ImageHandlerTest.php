<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\ImageHandler;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class to test Bolt\Twig\Handler\ImageHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ImageHandlerTest extends BoltUnitTest
{
    protected function setUp()
    {
        $app = $this->getApp();
        $files = $app['resources']->getPath('filespath');
        $fs = new Filesystem();
        $fs->copy(PHPUNIT_ROOT . '/resources/generic-logo.png', $files . '/generic-logo.png', true);
    }

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

    public function testImageInfoSafe()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->imageInfo('generic-logo.png', true);
        $this->assertNull($result);
    }

    public function testImageInfoNotReadable()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->imageInfo('koala.jpg', false);
        $this->assertFalse($result);
    }

    public function testImageInfo()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->imageInfo('generic-logo.png', false);
        $this->assertSame(624, $result['width']);
        $this->assertSame(351, $result['height']);
        $this->assertSame('jpeg', $result['type']);
        $this->assertSame('image/jpeg', $result['mime']);
        $this->assertRegExp('#1.7777#', (string) $result['aspectratio']);
        $this->assertSame('generic-logo.png', $result['filename']);
        $this->assertRegExp('#tests/phpunit/web-root/files/generic-logo.png#', $result['fullpath']);
        $this->assertSame('/files/generic-logo.png', $result['url']);
        $this->assertSame('', $result['exif']['latitude']);
        $this->assertFalse($result['exif']['longitude']);
        $this->assertFalse($result['exif']['datetime']);
        $this->assertFalse($result['exif']['orientation']);
        $this->assertRegExp('#1.7777#', (string) $result['exif']['aspectratio']);
        $this->asserttrue($result['landscape']);
        $this->assertFalse($result['portrait']);
        $this->assertFalse($result['square']);
    }

    public function testPopupEmptyFileName()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup();
        $this->assertSame('&nbsp;', $result);
    }

    public function testPopupFileNameOnly()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/100x100c/generic-logo.png" width="100" height="100" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupWidth()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png', 50);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/50x100c/generic-logo.png" width="50" height="100" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupHeight()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png', null, 50);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x50crop/generic-logo.png" width="" height="50" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupCrop()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png', null, null, 'f');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120f/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'fit');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120f/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);

        $result = $handler->popup('generic-logo.png', null, null, 'r');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120r/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'resize');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120r/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);

        $result = $handler->popup('generic-logo.png', null, null, 'b');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120b/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'borders');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120b/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);

        $result = $handler->popup('generic-logo.png', null, null, 'c');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'crop');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="" height="" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupFileNameArrayWithTitle()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup(['title' => 'Koala', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120crop/generic-logo.png" width="" height="" alt="Koala"></a>', $result);
    }

    public function testPopupFileNameArrayWithoutTitle()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup(['filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="generic-logo.png"><img src="/thumbs/160x120crop/generic-logo.png" width="" height="" alt="generic-logo.png"></a>', $result);
    }

    public function testPopupFileNameArrayWithAlt()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup(['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120crop/generic-logo.png" width="" height="" alt="Gum Leaves"></a>', $result);
    }
}
