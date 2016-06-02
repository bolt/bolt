<?php

namespace Bolt\Helpers\Image;

/**
 * Thumbnail helper class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Thumbnail
{
    /** @var string */
    protected $fileName;
    /** @var string */
    protected $title;
    /** @var string */
    protected $altTitle;
    /** @var integer */
    protected $height;
    /** @var integer */
    protected $width;
    /** @var string */
    protected $scale;
    /** @var array */
    protected $thumbConf;

    /**
     * Constructor.
     *
     * @param array $thumbConf Values from $app['config']->get('general/thumbnails')
     */
    public function __construct(array $thumbConf)
    {
        $this->thumbConf = $thumbConf;
    }

    /**
     * Get the file name.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set the file name.
     *
     * @param array|string $fileName
     *
     * @return Thumbnail
     */
    public function setFileName($fileName)
    {
        // After v1.5.1 we store image data as an array
        if (is_array($fileName)) {
            $rawFileName = isset($fileName['filename']) ? $fileName['filename'] : (isset($fileName['file']) ? $fileName['file'] : null);
            isset($fileName['title']) ? $this->title = $fileName['title'] : $rawFileName;
            isset($fileName['alt']) ? $this->altTitle = $fileName['alt'] : $rawFileName;
            $fileName = $rawFileName;
        }
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get the title.
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->title) {
            return $this->title;
        }

        return $this->altTitle;
    }

    /**
     * Set the title.
     *
     * @param string $title
     *
     * @return Thumbnail
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the alternative title.
     *
     * @return string
     */
    public function getAltTitle()
    {
        if ($this->altTitle) {
            return $this->altTitle;
        }

        return $this->title;
    }

    /**
     * Set the alternative title.
     *
     * @param string $altTitle
     *
     * @return Thumbnail
     */
    public function setAltTitle($altTitle)
    {
        $this->altTitle = $altTitle;

        return $this;
    }
    /**
     * Get the thumbnail width.
     *
     * @param boolean $round
     *
     * @return integer
     */
    public function getWidth($round = true)
    {
        if ($round) {
            return round($this->width);
        }

        return $this->width;
    }

    /**
     * Set the thumbnail width.
     *
     * @param integer $width
     * @param integer $default
     *
     * @return Thumbnail
     */
    public function setWidth($width, $default = 100)
    {
        if (!is_numeric($width)) {
            $width = empty($this->thumbConf['default_thumbnail'][0])
                ? $default
                : $this->thumbConf['default_thumbnail'][0];
        }
        $this->width = $width;

        return $this;
    }

    /**
     * Get the thumbnail height.
     *
     * @param boolean $round
     *
     * @return integer
     */
    public function getHeight($round = true)
    {
        if ($round) {
            return round($this->height);
        }

        return $this->height;
    }

    /**
     * Set the thumbnail height.
     *
     * @param integer $height
     * @param integer $default
     *
     * @return Thumbnail
     */
    public function setHeight($height, $default = 100)
    {
        if (!is_numeric($height)) {
            $height = empty($this->thumbConf['default_thumbnail'][1])
                ? $default
                : $this->thumbConf['default_thumbnail'][1];
        }
        $this->height = $height;

        return $this;
    }

    /**
     * Get the thumbnail scaling method.
     *
     * @return string
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Set the thumbnail scaling method.
     *
     * @param string $scale
     *
     * @return Thumbnail
     */
    public function setScale($scale)
    {
        $valid = ['b', 'c', 'f', 'r'];
        $scale = substr((string) $scale, 0, 1);
        $scale = in_array($scale, $valid)
            ? $scale
            : (!empty($this->thumbConf['cropping']) ? substr($this->thumbConf['cropping'], 0, 1) : 'c');

        $this->scale = $scale;

        return $this;
    }
}
