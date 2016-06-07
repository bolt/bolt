<?php
namespace Bolt\Asset\File;

use Bolt\Controller\Zone;

/**
 * File asset base class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class FileAssetBase implements FileAssetInterface
{
    /** @var string */
    protected $type;
    /** @var string */
    protected $path;
    /** @var string */
    protected $packageName;
    /** @var string */
    protected $url;
    /** @var boolean */
    protected $late;
    /** @var integer */
    protected $priority;
    /** @var string */
    protected $location;
    /** @var array */
    protected $attributes;
    /** @var string */
    protected $zone = Zone::FRONTEND;

    /**
     * Constructor.
     *
     * @param string $path
     * @param string $packageName
     */
    public function __construct($path = null, $packageName = null)
    {
        $this->path = $path;
        $this->packageName = $packageName;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function setFileName($fileName)
    {
        $this->path = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return FileAssetBase
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     *
     * @return FileAssetBase
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return FileAssetBase
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isLate()
    {
        return (boolean) $this->late;
    }

    /**
     * {@inheritdoc}
     */
    public function setLate($late)
    {
        $this->late = $late;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($raw = false)
    {
        if ($raw) {
            return $this->attributes;
        }

        return implode(' ', (array) $this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttribute($attribute)
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * {@inheritdoc}
     */
    public function setZone($zone)
    {
        $this->zone = $zone;

        return $this;
    }
}
