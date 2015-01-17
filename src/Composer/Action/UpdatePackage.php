<?php

namespace Bolt\Composer\Action;

use Bolt\Helpers\Arr;
use Composer\Installer;

/**
 * Composer update package class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UpdatePackage
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
    public function __construct(\Composer\IO\BufferIO $io, \Composer\Composer $composer, array $options)
    {
        $this->options = $options;
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * Update packages
     *
     * @param  $packages array Indexed array of package names to remove
     * @param  $options  array [Optional] changed option set
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute(array $packages = array(), array $options = null)
    {
        // Handle passed in options
        if (!is_null($options)) {
            $options = Arr::mergeRecursiveDistinct($this->options, $options);
        }

        $install = Installer::create($this->io, $this->composer);
        $config = $this->composer->getConfig();
        $optimize = $config->get('optimize-autoloader');

        $preferSource = false; // Forces installation from package sources when possible, including VCS information.
        $preferDist = true;

        switch ($config->get('preferred-install')) {
            case 'source':
                $preferSource = true;
                break;
            case 'dist':
                $preferDist = true;
                break;
            case 'auto':
            default:
                break;
        }

        /*
         * @todo: Enable setDumpAutoloader(), setPreferStable() and setPreferLowest()
         * for changes in Composer 1.0.0-alpha10
         */
        $install
            ->setDryRun($this->options['dryrun'])
            ->setVerbose($this->options['verbose'])
            ->setPreferSource($preferSource)
            ->setPreferDist($preferDist)
            ->setDevMode(!$this->options['nodev'])
//            ->setDumpAutoloader(!$this->options['noautoloader'])
            ->setRunScripts(!$this->options['noscripts'])
            ->setOptimizeAutoloader($optimize)
            ->setUpdate(true)
            ->setUpdateWhitelist($packages)
            ->setWhitelistDependencies($this->options['withdependencies'])
            ->setIgnorePlatformRequirements($this->options['ignoreplatformreqs'])
//            ->setPreferStable($this->options['preferstable'])
//            ->setPreferLowest($this->options['preferlowest'])
            ->disablePlugins();
        ;

        return $install->run();
    }
}
