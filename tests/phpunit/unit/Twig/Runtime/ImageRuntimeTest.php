<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Filesystem\Handler\Image;
use Bolt\Filesystem\Handler\ImageInterface;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\ImageRuntime;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class to test Bolt\Twig\Runtime\ImageRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ImageRuntimeTest extends BoltUnitTest
{
    protected function setUp()
    {
        $app = $this->getApp();
        $files = $app['path_resolver']->resolve('files');
        $fs = new Filesystem();
        $fs->copy(PHPUNIT_ROOT . '/resources/generic-logo.png', $files . '/generic-logo.png', true);
    }

    public function testImageFileNameLegacy()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->image('generic-logo.png');
        $this->assertStringStartsWith('/files/generic-logo.png', $result);
    }

    public function testImageFileNameArray()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->image(['filename' => 'generic-logo.png']);
        $this->assertStringStartsWith('/files/generic-logo.png', $result);
    }

    public function testImageFileNameWidthOnly()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->image(['filename' => 'generic-logo.png'], 20);
        $this->assertSame('/thumbs/20x120c/generic-logo.png', $result);
    }

    public function testImageFileNameHeightOnly()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->image(['filename' => 'generic-logo.png'], '', 20);
        $this->assertSame('/thumbs/160x20c/generic-logo.png', $result);
    }

    public function testImageFileNameWidthHeight()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->image(['filename' => 'generic-logo.png'], 20, 20);
        $this->assertSame('/thumbs/20x20c/generic-logo.png', $result);
    }

    public function testImageFileNameCrop()
    {
        $handler = $this->getImageRuntime();

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
        $handler = $this->getImageRuntime();

        $image = $handler->imageInfo('koala.jpg');
        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertInstanceOf(Image\Info::class, $image->getInfo());
    }

    public function testImageInfo()
    {
        $handler = $this->getImageRuntime();

        $image = $handler->imageInfo('generic-logo.png');
        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertInstanceOf(Image\Info::class, $image->getInfo());
    }

    public function testPopupEmptyFileName()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup();
        $this->assertSame('', $result);
    }

    public function testPopupFileNameOnly()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup('generic-logo.png');
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupWidth()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup('generic-logo.png', 50);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/50x120c/generic-logo.png" width="50" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupHeight()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup('generic-logo.png', null, 50);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x50c/generic-logo.png" width="160" height="50" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupCrop()
    {
        $handler = $this->getImageRuntime();

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
        $handler = $this->getImageRuntime();

        $result = $handler->popup(['title' => 'Koala', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Koala"></a>', $result);
    }

    public function testPopupFileNameArrayWithoutTitle()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup(['filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>', $result);
    }

    public function testPopupFileNameArrayWithAlt()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup(['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'], null, null, null, null);
        $this->assertSame('<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Gum Leaves"></a>', $result);
    }

    public function testShowImageEmptyFileName()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage();
        $this->assertSame('', $result);
    }

    public function testShowImageFileNameOnly()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage('generic-logo.png');
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">', $result);
    }

    public function testShowImageWidth()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage('generic-logo.png', 50);
        $this->assertSame('<img src="/thumbs/50x28c/generic-logo.png" width="50" height="28" alt="">', $result);
    }

    public function testShowImageHeight()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage('generic-logo.png', null, 50);
        $this->assertSame('<img src="/thumbs/89x50c/generic-logo.png" width="89" height="50" alt="">', $result);
    }

    public function testShowImageCrop()
    {
        $handler = $this->getImageRuntime();

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
        $handler = $this->getImageRuntime();

        $result = $handler->showImage(['title' => 'Koala', 'filename' => 'generic-logo.png'], null, null, null);
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="Koala">', $result);
    }

    public function testShowImageFileNameArrayWithoutTitle()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage(['filename' => 'generic-logo.png'], null, null, null);
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">', $result);
    }

    public function testShowImageFileNameArrayWithAlt()
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage(['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'], null, null, null);
        $this->assertSame('<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="Gum Leaves">', $result);
    }

    /**
     * @return ImageRuntime
     */
    protected function getImageRuntime()
    {
        $app = $this->getApp();

        return new ImageRuntime(
            $app['config'],
            $app['url_generator'],
            $app['filesystem'],
            $app['filesystem.matcher']
        );
    }
}
