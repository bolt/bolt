<?php

namespace Bolt\Composer\Action;

use Composer\Json\JsonFile;

/**
 * Initialise Composer JSON file class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class InitJson
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param $options  array
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     *
     * @param string $file
     * @param array  $data
     */
    public function execute($file, array $data = array())
    {
        $file = new JsonFile($file);
        $file->write($data);
    }
}
