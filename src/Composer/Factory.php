<?php

namespace Bolt\Composer;

use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Json\JsonFile;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Exception\CurlException;
use Silex\Application;

class Factory
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
     * @var Silex\Application
     */
    private $app;

    /**
     * @var boolean
     */
    private $downgradeSsl = false;

    /**
     * @var array
     */
    public $messages = array();

    /**
     *
     * @param Application          $app
     * @param Composer\Composer    $composer
     * @param Composer\IO\BufferIO $io
     * @param array                $options
     */
    public function __construct(Application $app, Composer\Composer $composer, Composer\IO\BufferIO $io, array $options)
    {
        $this->app = $app;
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Get a Composer object
     *
     * @return Composer\Composer
     */
    public function getComposer()
    {
        if (!$this->composer) {
            // Set working directory
            chdir($this->options['basedir']);

            // Use the factory to get a new Composer object
            $this->composer = \Composer\Factory::create($this->io, $this->options['composerjson'], true);

            if ($this->downgradeSsl) {
                $this->allowSslDowngrade(true);
            }
        }

        return $this->composer;
    }

    /**
     * Get a new Composer object
     *
     * @return Bolt\Composer\Factory
     */
    public function resetComposer()
    {
        $this->composer = null;

        return $this;
    }

    /**
     * Return the output from the last IO
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->io->getOutput();
    }

}
