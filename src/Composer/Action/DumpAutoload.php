<?php

namespace Bolt\Composer\Action;

class DumpAutoload
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
     * Dump autoloaders
     */
    public function execute()
    {
        $installationManager = $this->composer->getInstallationManager();
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $this->composer->getPackage();
        $config = $this->composer->getConfig();

        if ($this->options['optimizeautoloader']) {
            // Generating optimized autoload files
        } else {
            // Generating autoload files
        }

        $generator = $this->composer->getAutoloadGenerator();
        $generator->setDevMode(!$this->options['nodev']);
        $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $this->options['optimizeautoloader']);
    }
}
