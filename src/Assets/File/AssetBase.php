<?php
namespace Bolt\Assets\File;

/**
 * File asset base class.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 */
abstract class AssetBase
{
    /** @var string */
    protected $fileName;
    /** @var boolean */
    protected $late = false;
    /** @var integer */
    protected $priority;
    /** @var string */
    protected $attributes;

    /**
     * Constructor.
     *
     * @param string $fileName
     * @param array  $options  'late'     - True to add to the end of the HTML <body>
     *                         'priority' - Loading priority
     *                         'attrib'   - A string containing either/or 'defer', and 'async'
     */
    public function __construct($fileName, array $options = [])
    {
        $this->fileName   = $fileName;
        $this->late       = isset($options['late']) ? $options['late'] : false;
        $this->priority   = isset($options['priority']) ? $options['priority'] : 0;
        $this->attributes = isset($options['attrib']) ? $options['attrib'] : null;
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
