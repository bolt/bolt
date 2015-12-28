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
    protected $fileName;
    /** @var boolean */
    protected $late;
    /** @var integer */
    protected $priority;
    /** @var array */
    protected $attributes;
    /** @var string */
    protected $cacheHash;
    /** @var string */
    protected $zone = Zone::FRONTEND;

    /**
     * Constructor.
     *
     * @param string $fileName
     */
    public function __construct($fileName = null)
    {
        $this->fileName = $fileName;
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
        return $this->fileName;
    }

    /**
     * {@inheritdoc}
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

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
     * {@inheritDoc}
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
    public function getCacheHash()
    {
        return $this->cacheHash;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheHash($cacheHash)
    {
        $this->cacheHash = $cacheHash;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * @inheritDoc
     */
    public function setZone($zone)
    {
        $this->zone = $zone;

        return $this;
    }
}
