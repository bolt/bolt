<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Filesystem\Handler\Image;
use Bolt\Filesystem\Handler\ImageInterface;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\ImageRuntime;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Bolt\Twig\Runtime\ImageRuntime
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

    /**
     * @return array
     */
    public function providerImageMethod()
    {
        return [
            // File names
            'Legacy file name' => [
                '/files/generic-logo.png', 'generic-logo.png',
            ],
            'Array file name' => [
                '/files/generic-logo.png', 'generic-logo.png',
            ],

            // Specific dimensions
            'Width no height' => [
                '/thumbs/20x120c/generic-logo.png', ['filename' => 'generic-logo.png'], 20,
            ],
            'Height no width' => [
                '/thumbs/160x20c/generic-logo.png', ['filename' => 'generic-logo.png'], null, 20,
            ],
            'Height and width' => [
                '/thumbs/20x20c/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20,
            ],

            // Manipulation
            'Fit 20x20' => [
                '/thumbs/20x20f/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'fit',
            ],
            'Fit 20x20 abbreviated' => [
                '/thumbs/20x20f/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'f',
            ],
            'Resize 20x20' => [
                '/thumbs/20x20r/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'resize',
            ],
            'Resize 20x20 abbreviated' => [
                '/thumbs/20x20r/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'r',
            ],
            'Borders 20x20' => [
                '/thumbs/20x20b/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'borders',
            ],
            'Borders 20x20 abbreviated' => [
                '/thumbs/20x20b/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'b',
            ],
            'Crop 20x20' => [
                '/thumbs/20x20c/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'crop',
            ],
            'Crop 20x20 abbreviated' => [
                '/thumbs/20x20c/generic-logo.png', ['filename' => 'generic-logo.png'], 20, 20, 'c',
            ],
        ];
    }

    /**
     * @dataProvider providerImageMethod
     *
     * @param string       $expect
     * @param string|array $fileName
     * @param int|null     $width
     * @param int|null     $height
     * @param string|null  $crop
     */
    public function testImageMethod($expect, $fileName, $width = null, $height = null, $crop = null)
    {
        $app = $this->getApp();
        $handler = $this->getImageRuntime();
        $env = $app['twig'];

        $result = $handler->image($env, $fileName, $width, $height, $crop);
        $this->assertStringStartsWith($expect, $result);
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

    public function providerPopup()
    {
        return [
            // File names
            'Empty file name' => [
                '',
                null,
            ],
            'testPopupFileNameOnly' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png',
            ],
            'File name array with title' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Koala"></a>',
                ['title' => 'Koala', 'filename' => 'generic-logo.png'],
            ],
            'File name array without title' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                ['filename' => 'generic-logo.png'],
            ],
            'File name array with alt' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Koala"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Gum Leaves"></a>',
                ['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'],
            ],

            // Specific dimensions
            'testPopupWidth' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/50x120c/generic-logo.png" width="50" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', 50,
            ],
            'testPopupHeight' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x50c/generic-logo.png" width="160" height="50" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, 50,
            ],
            'Width & height' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/50x50c/generic-logo.png" width="50" height="50" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', 50, 50,
            ],

            // Manipulation
            'Fit' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120f/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'fit',
            ],
            'Fit abbreviated' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120f/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'f',
            ],
            'Resize' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120r/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'resize',
            ],
            'Resize abbreviated' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120r/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'r`',
            ],
            'borders' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120b/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'b',
            ],
            'Borders abbreviated' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120b/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'borders',
            ],
            'Crop' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'crop',
            ],
            'Crop abbreviated' => [
                '<a href="/thumbs/1000x750r/generic-logo.png" class="magnific" title="Image: generic-logo.png"><img src="/thumbs/160x120c/generic-logo.png" width="160" height="120" alt="Image: generic-logo.png"></a>',
                'generic-logo.png', null, null, 'c',
            ],
        ];
    }

    /**
     * @dataProvider providerPopup
     *
     * @param string       $expect
     * @param string|array $fileName
     * @param int|null     $width
     * @param int|null     $height
     * @param string|null  $crop
     * @param string|null  $title
     */
    public function testPopup($expect, $fileName, $width = null, $height = null, $crop = null, $title = null)
    {
        $handler = $this->getImageRuntime();

        $result = $handler->popup($fileName, $width, $height, $crop, $title);
        $this->assertSame($expect, $result);
    }

    /**
     * @return array
     */
    public function providerShowImage()
    {
        return [
            // File names
            'Empty file name' => [
                '', null,
            ],
            'File name only' => [
                '<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png',
            ],
            'File name array with title' => [
                '<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="Koala">',
                ['title' => 'Koala', 'filename' => 'generic-logo.png'],
            ],
            'File name array without title' => [
                '<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">',
                ['filename' => 'generic-logo.png'],
            ],
            'File name array with alt' => [
                '<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="Gum Leaves">',
                ['title' => 'Koala', 'alt' => 'Gum Leaves', 'filename' => 'generic-logo.png'],
            ],

            // Specific dimensions
            'Width only' => [
                '<img src="/thumbs/50x28c/generic-logo.png" width="50" height="28" alt="">',
                'generic-logo.png', 50,
            ],
            'Height only' => [
                '<img src="/thumbs/89x50c/generic-logo.png" width="89" height="50" alt="">',
                'generic-logo.png', null, 50,
            ],
            'Width & height' => [
                '<img src="/thumbs/50x50c/generic-logo.png" width="50" height="50" alt="">',
                'generic-logo.png', 50, 50,
            ],

            // Manipulation
            'Fit' => [
                '<img src="/thumbs/1000x750f/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'fit',
            ],
            'Fit abbreviated' => [
                '<img src="/thumbs/1000x750f/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'f',
            ],
            'Resize' => [
                '<img src="/thumbs/1000x750r/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'resize',
            ],
            'Resize abbreviated' => [
                '<img src="/thumbs/1000x750r/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'r`',
            ],
            'borders' => [
                '<img src="/thumbs/1000x750b/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'b',
            ],
            'Borders abbreviated' => [
                '<img src="/thumbs/1000x750b/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'borders',
            ],
            'Crop' => [
                '<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'crop',
            ],
            'Crop abbreviated' => [
                '<img src="/thumbs/1000x750c/generic-logo.png" width="1000" height="750" alt="">',
                'generic-logo.png', null, null, 'c',
            ],
        ];
    }

    /**
     * @dataProvider providerShowImage
     *
     * @param string       $expect
     * @param string|array $fileName
     * @param int|null     $width
     * @param int|null     $height
     * @param string|null  $crop
     */
    public function testShowImage($expect, $fileName, $width = null, $height = null, $crop = null)
    {
        $handler = $this->getImageRuntime();

        $result = $handler->showImage($fileName, $width, $height, $crop);
        $this->assertSame($expect, $result);
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
