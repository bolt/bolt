<?php

namespace Bolt\Twig\Handler;

use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use PHPExif\Exif;
use PHPExif\Reader\Reader as ExifReader;
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
     * @param string         $filename Target filename
     * @param string|integer $width    Target width
     * @param string|integer $height   Target height
     * @param string         $crop     String identifier for cropped images
     *
     * @return string Image path
     */
    public function image($filename, $width = '', $height = '', $crop = '')
    {
        if ($width != '' || $height != '') {
            // You don't want the image, you just want a thumbnail.
            return $this->thumbnail($filename, $width, $height, $crop);
        }

        // After v1.5.1 we store image data as an array
        if (is_array($filename)) {
            $filename = isset($filename['filename']) ? $filename['filename'] : $filename['file'];
        }

        $image = sprintf(
            '%sfiles/%s',
            $this->app['paths']['root'],
            Lib::safeFilename($filename)
        );

        return $image;
    }

    /**
     * Get an array with the dimensions of an image, together with its
     * aspectratio and some other info.
     *
     * @param string  $filename
     * @param boolean $safe
     *
     * @return array Specifics
     */
    public function imageInfo($filename, $safe)
    {
        // This function is vulnerable to path traversal, so blocking it in
        // safe mode for now.
        if ($safe) {
            return null;
        }

        $fullpath = sprintf('%s/%s', $this->app['resources']->getPath('filespath'), $filename);

        if (!is_readable($fullpath) || !is_file($fullpath)) {
            return false;
        }

        $types = array(
            0 => 'unknown',
            1 => 'gif',
            2 => 'jpeg',
            3 => 'png',
            4 => 'swf',
            5 => 'psd',
            6 => 'bmp'
        );

        // Get the dimensions of the image
        $imagesize = getimagesize($fullpath);

        // Get the aspectratio
        if ($imagesize[1] > 0) {
            $ar = $imagesize[0] / $imagesize[1];
        } else {
            $ar = 0;
        }

        $info = array(
            'width'       => $imagesize[0],
            'height'      => $imagesize[1],
            'type'        => $types[$imagesize[2]],
            'mime'        => $imagesize['mime'],
            'aspectratio' => $ar,
            'filename'    => $filename,
            'fullpath'    => realpath($fullpath),
            'url'         => str_replace('//', '/', $this->app['resources']->getUrl('files') . $filename)
        );

        /** @var $reader \PHPExif\Reader\Reader */
        $reader = ExifReader::factory(ExifReader::TYPE_NATIVE);

        try {
            // Get the EXIF data of the image
            $exif = $reader->read($fullpath);
        } catch (\RuntimeException $e) {
            // No EXIF data… create an empty object.
            $exif = new Exif();
        }

        // GPS coordinates
        $gps = $exif->getGPS();
        $gps = explode(',', $gps);

        // If the picture is turned by exif, ouput the turned aspectratio
        if (in_array($exif->getOrientation(), array(6, 7, 8))) {
            $exifturned = $imagesize[1] / $imagesize[0];
        } else {
            $exifturned = $ar;
        }

        // Output the relevant EXIF info
        $info['exif'] = array(
            'latitude'    => isset($gps[0]) ? $gps[0] : false,
            'longitude'   => isset($gps[1]) ? $gps[1] : false,
            'datetime'    => $exif->getCreationDate(),
            'orientation' => $exif->getOrientation(),
            'aspectratio' => $exifturned ? : false
        );

        // Landscape if aspectratio > 5:4
        $info['landscape'] = ($ar >= 1.25) ? true : false;

        // Portrait if aspectratio < 4:5
        $info['portrait'] = ($ar <= 0.8) ? true : false;

        // Square-ish, if neither portrait or landscape
        $info['square'] = !$info['landscape'] && !$info['portrait'];

        return $info;
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
     * @param string  $filename Image filename
     * @param integer $width    Image width
     * @param integer $height   Image height
     * @param string  $crop     Crop image string identifier
     * @param string  $title    Display title for image
     *
     * @return string HTML output
     */
    public function popup($filename = '', $width = 100, $height = 100, $crop = '', $title = '')
    {
        if (!empty($filename)) {
            $thumbconf = $this->app['config']->get('general/thumbnails');

            $fullwidth = !empty($thumbconf['default_image'][0]) ? $thumbconf['default_image'][0] : 1000;
            $fullheight = !empty($thumbconf['default_image'][1]) ? $thumbconf['default_image'][1] : 800;

            $thumbnail = $this->thumbnail($filename, $width, $height, $crop);
            $large = $this->thumbnail($filename, $fullwidth, $fullheight, 'r');

            if (empty($title)) {
                // try to get title from filename array
                if (is_array($filename) && isset($filename['title'])) {
                    $title = $filename['title'];
                } else {
                    // fallback to filename from array
                    if (is_array($filename) && isset($filename['filename'])) {
                        $title = $filename['filename'];
                    } else {
                        // fallback to filename as provided (string)
                        $title = sprintf('%s: %s', Trans::__('Image'), $filename);
                    }
                }
            }
            
            if (is_array($filename) && isset($filename['alt'])) {
                $alt = $filename['alt'];
            } else {
                $alt = $title;
            }

            $output = sprintf(
                '<a href="%s" class="magnific" title="%s"><img src="%s" width="%s" height="%s" alt="%s"></a>',
                $large,
                $title,
                $thumbnail,
                $width,
                $height,
                $alt
            );
        } else {
            $output = '&nbsp;';
        }

        return $output;
    }

    /**
     * Helper function to show an image on a rendered page.
     *
     * Set width or height parameter to '0' for proportional scaling.
     * Set them both to '0' to get original width and height.
     *
     * Example: {{ content.image|showimage(320, 240) }}
     * Example: {{ showimage(content.image, 320, 240) }}
     *
     * @param string  $filename Image filename
     * @param integer $width    Image width
     * @param integer $height   Image height
     * @param string  $crop     Crop image string identifier
     *
     * @return string HTML output
     */
    public function showImage($filename = '', $width = 0, $height = 0, $crop = '')
    {
        if (empty($filename)) {
            return '&nbsp;';
        } else {
            $width = intval($width);
            $height = intval($height);

            if (isset($filename['alt'])) {
                $alt = $filename['alt'];
            } elseif (isset($filename['title'])) {
                $alt = $filename['title'];
            } else {
                $alt = '';
            }

            if ($width === 0 || $height === 0) {
                if (is_array($filename)) {
                    $filename = isset($filename['filename']) ? $filename['filename'] : $filename['file'];
                }

                $info = $this->imageInfo($filename, false);

                if ($width !== 0) {
                    $height = round($width / $info['aspectratio']);
                } elseif ($height !== 0) {
                    $width = round($height * $info['aspectratio']);
                } else {
                    $width = $info['width'];
                    $height = $info['height'];
                }
            }

            $image = $this->thumbnail($filename, $width, $height, $crop);

            return '<img src="' . $image . '" width="' . $width . '" height="' . $height . '" alt="'. $alt .'">';
        }
    }

    /**
     * Helper function to make a path to an image thumbnail.
     *
     * @param string     $filename Target filename
     * @param string|int $width    Target width
     * @param string|int $height   Target height
     * @param string     $zoomcrop Zooming and cropping: Set to 'f(it)', 'b(orders)', 'r(esize)' or 'c(rop)'
     *                             Set width or height parameter to '0' for proportional scaling
     *                             Setting them to '' uses default values.
     *
     * @return string Thumbnail path
     */
    public function thumbnail($filename, $width = '', $height = '', $zoomcrop = 'crop')
    {
        if (!is_numeric($width)) {
            $thumbconf = $this->app['config']->get('general/thumbnails');
            $width = empty($thumbconf['default_thumbnail'][0]) ? 100 : $thumbconf['default_thumbnail'][0];
        }

        if (!is_numeric($height)) {
            $thumbconf = $this->app['config']->get('general/thumbnails');
            $height = empty($thumbconf['default_thumbnail'][1]) ? 100 : $thumbconf['default_thumbnail'][1];
        }

        switch ($zoomcrop) {
            case 'fit':
            case 'f':
                $scale = 'f';
                break;

            case 'resize':
            case 'r':
                $scale = 'r';
                break;

            case 'borders':
            case 'b':
                $scale = 'b';
                break;

            case 'crop':
            case 'c':
                $scale = 'c';
                break;

            default:
                $scale = !empty($thumbconf['cropping']) ? $thumbconf['cropping'] : 'c';
        }

        // After v1.5.1 we store image data as an array
        if (is_array($filename)) {
            $filename = isset($filename['filename']) ? $filename['filename'] : $filename['file'];
        }

        $path = $this->app['url_generator']->generate(
            'thumb',
            array(
                'thumb' => round($width) . 'x' . round($height) . $scale . '/' . $filename,
            )
        );

        return $path;
    }
}
