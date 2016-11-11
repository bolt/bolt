<?php

namespace Bolt\Twig\Handler;

use Bolt\Filesystem\Handler\ImageInterface;
use Bolt\Filesystem\Handler\NullableImage;
use Bolt\Helpers\Image\Thumbnail;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex;

/**
 * Bolt specific Twig functions and filters that provide image support
 *
 * @internal
 */
class ImageHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Helper function to make a path to an image.
     *
     * @param string         $fileName Target filename
     * @param string|integer $width    Target width
     * @param string|integer $height   Target height
     * @param string         $crop     String identifier for cropped images
     *
     * @return string Image path
     */
    public function image($fileName = null, $width = null, $height = null, $crop = null)
    {
        //Check if it's an alias as the only parameter after $filename
        if ($width && !$height && !$crop && $this->isAlias($width)) {
            return $this->getAliasedUri($filename, $width);
        }

        if ($width || $height) {
            // You don't want the image, you just want a thumbnail.
            return $this->thumbnail($fileName, $width, $height, $crop);
        }

        // After v1.5.1 we store image data as an array
        if (is_array($fileName)) {
            $fileName = isset($fileName['filename']) ? $fileName['filename'] : (isset($fileName['file']) ? $fileName['file'] : '');
        }

        $image = sprintf(
            '%s%s',
            $this->app['resources']->getUrl('files'),
            Lib::safeFilename($fileName)
        );

        return $image;
    }

    /**
     * Get an image.
     *
     * @param string $fileName
     * @param string $safe
     *
     * @return \Bolt\Filesystem\Handler\ImageInterface
     */
    public function imageInfo($fileName, $safe)
    {
        if ($fileName instanceof ImageInterface) {
            return $fileName;
        } elseif ($safe) {
            return null;
        }

        $image = $this->app['filesystem']->getFile('files://' . $fileName, new NullableImage());

        return $image;
    }

    /**
     * Helper function to wrap an image in a Magnific popup HTML tag, with thumbnail.
     *
     * example: {{ content.image|popup(320, 240) }}
     * example: {{ popup(content.image, 320, 240) }}
     * example: {{ content.image|popup(width=320, height=240, title="My Image") }}
     *
     * Note: This function used to be called 'fancybox', but Fancybox was
     * deprecated in favour of the Magnific Popup library.
     *
     * @param string|array $fileName Image file name
     * @param integer      $width    Image width
     * @param integer      $height   Image height
     * @param string       $crop     Crop image string identifier
     * @param string       $title    Display title for image
     *
     * @return string HTML output
     */
    public function popup($fileName = null, $width = null, $height = null, $crop = null, $title = null)
    {
        if (empty($fileName)) {
            return '';
        }

        $thumbconf = $this->app['config']->get('general/thumbnails');
        $fullwidth = !empty($thumbconf['default_image'][0]) ? $thumbconf['default_image'][0] : 1000;
        $fullheight = !empty($thumbconf['default_image'][1]) ? $thumbconf['default_image'][1] : 750;

        $thumb = $this->getThumbnail($fileName, $width, $height, $crop);
        $largeThumb = $this->getThumbnail($fileName, $fullwidth, $fullheight, 'r');

        // BC Nightmare… If we're passed a title, use it, if not we might have
        // one in the $fileName array, else use the file name
        $title = $title ?: $thumb->getTitle() ?: sprintf('%s: %s', Trans::__('Image'), $thumb->getFileName());
        $altTitle = $thumb->getAltTitle() ?: $title;

        if ($this->getThumbnailUri($largeThumb)) {
            $output = sprintf(
                '<a href="%s" class="magnific" title="%s"><img src="%s" width="%s" height="%s" alt="%s"></a>',
                $this->getThumbnailUri($largeThumb),
                $title,
                $this->getThumbnailUri($thumb),
                $thumb->getWidth(),
                $thumb->getHeight(),
                $altTitle
            );
        } else {
            $output = '';
        }

        return $output;
    }

    /**
     * Helper function to show an image on a rendered page.
     *
     * Set width or height parameter to '0' for proportional scaling.
     * Set them both to null (or not at all) to get the default size from config.yml.
     *
     * Example: {{ content.image|showimage(320, 240) }}
     * Example: {{ showimage(content.image, 320, 240) }}
     *
     * @param string  $fileName Image filename
     * @param integer $width    Image width
     * @param integer $height   Image height
     * @param string  $crop     Crop image string identifier
     *
     * @return string HTML output
     */
    public function showImage($fileName = null, $width = null, $height = null, $crop = null)
    {
        if (empty($fileName)) {
            return '';
        }
        $thumb = $this->getThumbnail($fileName, $width, $height, $crop);

        if ($width === null && $height === null) {
            $thumbconf = $this->app['config']->get('general/thumbnails');
            $width = !empty($thumbconf['default_image'][0]) ? $thumbconf['default_image'][0] : 1000;
            $height = !empty($thumbconf['default_image'][1]) ? $thumbconf['default_image'][1] : 750;
            $thumb->setWidth($width);
            $thumb->setHeight($height);
        } elseif ($width === null xor $height === null) {
            $info = $this->imageInfo($thumb->getFileName(), false)->getInfo();

            if ($width !== null) {
                $width = min($width, $info->getWidth());
                $thumb->setHeight(round($width / $info->getAspectRatio()));
            } elseif ($height !== null) {
                $height = min($height, $info->getHeight());
                $thumb->setWidth(round($height * $info->getAspectRatio()));
            } else {
                $thumb->setWidth($info->getWidth());
                $thumb->setHeight($info->getHeight());
            }
        }

        return sprintf(
            '<img src="%s" width="%s" height="%s" alt="%s">',
            $this->getThumbnailUri($thumb),
            $thumb->getWidth(),
            $thumb->getHeight(),
            $thumb->getAltTitle()
        );
    }

    /**
     * Helper function to make a path to an image thumbnail.
     *
     * @param string     $fileName Target filename
     * @param string|int $width    Target width
     * @param string|int $height   Target height
     * @param string     $crop     Zooming and cropping: Set to 'f(it)', 'b(orders)', 'r(esize)' or 'c(rop)'
     *                             Set width or height parameter to '0' for proportional scaling
     *                             Setting them to '' uses default values.
     *
     * @return string Relative URL of the thumbnail
     */
    public function thumbnail($fileName = null, $width = null, $height = null, $crop = null)
    {
        //Check if it's an alias as the only parameter after $filename
        if ($width && !$height && !$crop && $this->isAlias($width)) {
            return $this->getAliasedUri($fileName, $width);
        }

        $thumb = $this->getThumbnail($fileName, $width, $height, $crop);

        return $this->getThumbnailUri($thumb);
    }

    /**
     * Get a thumbnail object.
     *
     * @param string|array $fileName
     * @param integer      $width
     * @param integer      $height
     * @param string       $scale
     *
     * @return Thumbnail
     */
    private function getThumbnail($fileName = null, $width = null, $height = null, $scale = null)
    {
        $thumb = new Thumbnail($this->app['config']->get('general/thumbnails'));
        $thumb
            ->setFileName($fileName)
            ->setWidth($width)
            ->setHeight($height)
            ->setScale($scale)
        ;

        return $thumb;
    }

    /**
     * Get the thumbnail relative URI, using width, height and action.
     *
     * @param Thumbnail $thumb
     *
     * @return mixed
     */
    private function getThumbnailUri(Thumbnail $thumb)
    {
        if ($thumb->getFileName() == null) {
            return false;
        }

        return $this->app['url_generator']->generate(
            'thumb',
            [
                'width'  => $thumb->getWidth(),
                'height' => $thumb->getHeight(),
                'action' => $thumb->getScale(),
                'file'   => $thumb->getFileName(),
            ]
        );
    }

    /**
     * Get the thumbnail relative URI, using an alias.
     *
     * @param mixed  $filename
     * @param string $alias
     *
     * @return mixed
     */
    private function getAliasedUri($filename, $alias)
    {
        if (!$this->isAlias($alias)) {
            return false;
        }

        // If we're passing in an image as array, instead of a single filename.
        if (is_array($filename) && isset($filename['file'])) {
            $filename = $filename['file'];
        }

        return $this->app['url_generator']->generate(
            'thumb_alias',
            [
                'alias'  => $alias,
                'file'   => $filename,
            ]
        );
    }

    private function isAlias($alias)
    {
        return (bool) $this->app['config']->get('theme/thumbnails/aliases/' . $alias, false);
    }
}
