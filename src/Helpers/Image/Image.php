<?php

namespace Bolt\Helpers\Image;

use PHPExif\Exif;
use PHPExif\Reader\Reader as ExifReader;

/**
 * Image helper class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Image implements \ArrayAccess
{
    /** @var string */
    protected $filename;
    /** @var string */
    protected $fullpath;
    /** @var string */
    protected $url;
    /** @var integer */
    protected $width;
    /** @var integer */
    protected $height;
    /** @var string */
    protected $type;
    /** @var string */
    protected $mime;
    /** @var float */
    protected $aspectratio;
    /** @var array */
    protected $exif;
    /** @var boolean */
    protected $landscape;
    /** @var boolean */
    protected $portrait;
    /** @var boolean */
    protected $square;

    /**
     * Constructor.
     *
     * @param string $fileName
     * @param string $boltFilesUri
     * @param string $boltFilesUri
     */
    public function __construct($fileName, $boltFilesPath, $boltFilesUri)
    {
        $fileFullPath = sprintf('%s/%s', $boltFilesPath, $fileName);
        $fileFullPath = realpath($fileFullPath);
        if (!is_readable($fileFullPath)) {
            return;
        }
        $this->filename = basename($fileFullPath);
        $this->fullpath = $fileFullPath;
        $this->url = str_replace('//', '/', $boltFilesUri . $this->filename);

        $this->setImageAttributes();
        $this->setAspectRatio();
        $this->setImageExifData();
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    /**
     * Set the base image attributes.
     */
    protected function setImageAttributes()
    {
        $types = [
            0 => 'unknown',
            1 => 'gif',
            2 => 'jpeg',
            3 => 'png',
            4 => 'swf',
            5 => 'psd',
            6 => 'bmp'
        ];

        // Get the dimensions of the image
        $attributes = getimagesize($this->fullpath);

        $this->width = $attributes[0];
        $this->height = $attributes[1];
        $this->type = $types[$attributes[2]];
        $this->mime = $attributes['mime'];
    }

    /**
     * Set the aspect ratio for the image.
     */
    protected function setAspectRatio()
    {
        $this->aspectratio = $this->height > 0 ? $this->width / $this->height : 0;

        // Landscape if aspectratio > 5:4
        $this->landscape = ($this->aspectratio >= 1.25) ? true : false;

        // Portrait if aspectratio < 4:5
        $this->portrait = ($this->aspectratio <= 0.8) ? true : false;

        // Square-ish, if neither portrait or landscape
        $this->square = !$this->landscape && !$this->portrait;
    }

    /**
     * Set the EXIF data for the image.
     */
    protected function setImageExifData()
    {
        $exif = $this->getExif();

        // GPS coordinates
        $gps = $exif->getGPS();
        $gps = explode(',', $gps);

        // If the picture is turned by exif, ouput the turned aspectratio
        if (in_array($exif->getOrientation(), [6, 7, 8])) {
            $exifturned = $this->height / $this->width;
        } else {
            $exifturned = $this->aspectratio;
        }

        // Output the relevant EXIF info
        $this->exif = [
            'latitude'    => isset($gps[0]) ? $gps[0] : false,
            'longitude'   => isset($gps[1]) ? $gps[1] : false,
            'datetime'    => $exif->getCreationDate(),
            'orientation' => $exif->getOrientation(),
            'aspectratio' => $exifturned ?: false
        ];
    }

    /**
     * Get an EXIF object.
     *
     * @return \PHPExif\Exif
     */
    protected function getExif()
    {
        /** @var $reader \PHPExif\Reader\Reader */
        $reader = ExifReader::factory(ExifReader::TYPE_NATIVE);

        try {
            // Get the EXIF data of the image
            $exif = $reader->read($this->fullpath);
        } catch (\RuntimeException $e) {
            // No EXIF data… create an empty object.
            $exif = new Exif();
        }

        return $exif;
    }
}
