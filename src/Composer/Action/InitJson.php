<?php

namespace Bolt\Composer\Action;

use Composer\Json\JsonFile;

class InitJson
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Composer\IO\BufferIO
     */
    private $io;

    /**
     * @var Composer\Composer
     */
    private $composer;

    /**
     * @param $io       Composer\IO\BufferIO
     * @param $composer Composer\Composer
     * @param $options  array
     */
    public function __construct($io, $composer, $options)
    {
        $this->options = $options;
        $this->io = $io;
        $this->composer = $composer;
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
