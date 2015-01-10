<?php

namespace Bolt\Composer\Action;

use Composer\Config\JsonConfigSource;
use Composer\Factory;
use Composer\Installer;
use Composer\Json\JsonFile;
use Silex\Application;

class RemovePackage
{
    /**
     * @var Silex\Application
     */
    private $app;

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
     * @param $app      Silex\Application
     * @param $io       Composer\IO\BufferIO
     * @param $composer Composer\Composer
     * @param $options  array
     */
    public function __construct(Application $app, $io, $composer, $options)
    {
        $this->app = $app;
        $this->options = $options;
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * Remove packages from the root install
     *
     * @param  $packages array Indexed array of package names to remove
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute(array $packages)
    {
        $jsonFile = new JsonFile($this->options['composerjson']);
        $composerDefinition = $jsonFile->read();
        $composerBackup = file_get_contents($jsonFile->getPath());

        $json = new JsonConfigSource($jsonFile);

        $type = $this->options['dev'] ? 'require-dev' : 'require';
        $altType = !$this->options['dev'] ? 'require-dev' : 'require';

        // Remove packages from JSON
        foreach ($packages as $package) {
            if (isset($composerDefinition[$type][$package])) {
                $json->removeLink($type, $package);
            }
        }

        // Reload Composer config
        $this->composer = $this->app['extend.runner']->getComposer();

        $install = Installer::create($this->io, $this->composer);

        $install
            ->setVerbose($this->options['verbose'])
            ->setDevMode(!$this->options['updatenodev'])
            ->setUpdate(true)
            ->setUpdateWhitelist($packages)
            ->setWhitelistDependencies($this->options['updatewithdependencies'])
            ->setIgnorePlatformRequirements($this->options['ignoreplatformreqs'])
        ;

        $status = $install->run();

        if ($status !== 0) {
            // Write out original JSON file
            file_put_contents($jsonFile->getPath(), $composerBackup);
        }

        return $status;
    }
}
