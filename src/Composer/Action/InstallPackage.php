<?php

namespace Bolt\Composer\Action;

use Composer\Installer;

/**
 * Composer package install class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class InstallPackage
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
     * Install packages
     *
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute()
    {
        $install = Installer::create($this->io, $this->composer);
        $config = $this->composer->getConfig();
        $optimize = $config->get('optimize-autoloader');

        $preferSource = false;
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
                // noop
                break;
        }

        if ($config->get('prefer-source') || $config->get('prefer-dist')) {
            $preferSource = $config->get('prefer-source');
            $preferDist = $config->get('prefer-dist');
        }

        /* @todo: Enable setDumpAutoloader() for changes in Composer 1.0.0-alpha10 */
        $install
            ->setDryRun($this->options['dry-run'])
            ->setVerbose($this->options['verbose'])
            ->setPreferSource($preferSource)
            ->setPreferDist($preferDist)
            ->setDevMode(!$this->options['no-dev'])
//            ->setDumpAutoloader(!$this->options['no-autoloader'])
            ->setRunScripts(!$this->options['no-scripts'])
            ->setOptimizeAutoloader($optimize)
            ->setIgnorePlatformRequirements($this->options['ignore-platform-reqs'])
            ->setUpdate(true)
        ;

        return $install->run();
    }
}
