<?php
namespace Bolt\Asset\File;

use Bolt\Asset\AssetInterface;

/**
 * File asset base class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class FileAssetBase implements AssetInterface
{
    /** @var string */
    protected $fileName;
    /** @var boolean */
    protected $late = false;
    /** @var integer */
    protected $priority;
    /** @var string */
    protected $attributes;
    /** @var string */
    protected $cacheHash;

    /**
     * Constructor.
     *
     * @param string $fileName  Relative path and file name
     * @param string $cacheHash Hash string made from seed and file modification time
     * @param array  $options   'late'     - True to add to the end of the HTML <body>
     *                          'priority' - Loading priority
     *                          'attrib'   - A string containing either/or 'defer', and 'async'
     */
    public function __construct($fileName, $cacheHash, array $options = [])
    {
        $this->fileName   = $fileName;
        $this->late       = isset($options['late']) ? $options['late'] : false;
        $this->priority   = isset($options['priority']) ? $options['priority'] : 0;
        $this->attributes = isset($options['attrib']) ? $options['attrib'] : null;
        $this->cacheHash  = $cacheHash;
    }

    public function isLate()
    {
        return $this->late;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getPriority()
    {
        return $this->priority;
    }
}
