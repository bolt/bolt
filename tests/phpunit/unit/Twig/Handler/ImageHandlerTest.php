<?php

namespace Bolt\Tests\Twig;

use Bolt\Filesystem\Handler\Image;
use Bolt\Filesystem\Handler\ImageInterface;
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
        $this->assertSame('/thumbs/20x120c/generic-logo.png', $result);
    }

    public function testImageFileNameHeightOnly()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->image(['filename' => 'generic-logo.png'], '', 20);
        $this->assertSame('/thumbs/160x20c/generic-logo.png', $result);
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

    public function testImageInfoNotReadable()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $image = $handler->imageInfo('koala.jpg', false);
        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertInstanceOf(Image\Info::class, $image->getInfo());
    }

    public function testImageInfo()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $image = $handler->imageInfo('generic-logo.png', false);
        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertInstanceOf(Image\Info::class, $image->getInfo());
    }

    public function testPopupEmptyFileName()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup();
        $this->assertSame('', $result);
    }

    public function testPopupFileNameOnly()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupWidth()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png', 50);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/50x120c/generic-logo.png" width="50" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupHeight()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png', null, 50);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x50c/generic-logo.png" width="160" height="50" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupCrop()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup('generic-logo.png', null, null, 'f');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120f/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'fit');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120f/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);

        $result = $handler->popup('generic-logo.png', null, null, 'r');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120r/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'resize');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120r/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);

        $result = $handler->popup('generic-logo.png', null, null, 'b');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120b/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'borders');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120b/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);

        $result = $handler->popup('generic-logo.png', null, null, 'c');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
        $result = $handler->popup('generic-logo.png', null, null, 'crop');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupFileNameArrayWithTitle()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup(['title' => 'Koala', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Koala"></a>', $result);
    }

    public function testPopupFileNameArrayWithoutTitle()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup(['filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupFileNameArrayWithAlt()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->popup(['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Gum Leaves"></a>', $result);
    }

    public function testShowImageEmptyFileName()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage();
        $this->assertSame('', $result);
    }

    public function testShowImageFileNameOnly()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage('generic-logo.png');
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">', $result);
    }

    public function testShowImageWidth()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage('generic-logo.png', 50);
        $this->assertSame('<img src="/thumbs/50x28c/generic-logo.png" width="50" height="28" alt="">', $result);
    }

    public function testShowImageHeight()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage('generic-logo.png', null, 50);
        $this->assertSame('<img src="/thumbs/89x50c/generic-logo.png" width="89" height="50" alt="">', $result);
    }

    public function testShowImageCrop()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage('generic-logo.png', null, null, 'f');
        $this->assertSame('<img src="/thumbs/1000x750f/generic-logo.png" width="1000" height="750" alt="">', $result);
        $result = $handler->showImage('generic-logo.png', null, null, 'fit');
        $this->assertSame('<img src="/thumbs/1000x750f/generic-logo.png" width="1000" height="750" alt="">', $result);

        $result = $handler->showImage('generic-logo.png', null, null, 'r');
        $this->assertSame('<img src="/thumbs/1000x750r/generic-logo.png" width="1000" height="750" alt="">', $result);
        $result = $handler->showImage('generic-logo.png', null, null, 'resize');
        $this->assertSame('<img src="/thumbs/1000x750r/generic-logo.png" width="1000" height="750" alt="">', $result);

        $result = $handler->showImage('generic-logo.png', null, null, 'b');
        $this->assertSame('<img src="/thumbs/1000x750b/generic-logo.png" width="1000" height="750" alt="">', $result);
        $result = $handler->showImage('generic-logo.png', null, null, 'borders');
        $this->assertSame('<img src="/thumbs/1000x750b/generic-logo.png" width="1000" height="750" alt="">', $result);

        $result = $handler->showImage('generic-logo.png', null, null, 'c');
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">', $result);
        $result = $handler->showImage('generic-logo.png', null, null, 'crop');
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">', $result);
    }

    public function testShowImageFileNameArrayWithTitle()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage(['title' => 'Koala', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="Koala">', $result);
    }

    public function testShowImageFileNameArrayWithoutTitle()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage(['filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">', $result);
    }

    public function testShowImageFileNameArrayWithAlt()
    {
        $app = $this->getApp();
        $handler = new ImageHandler($app);

        $result = $handler->showImage(['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="Gum Leaves">', $result);
    }
}
