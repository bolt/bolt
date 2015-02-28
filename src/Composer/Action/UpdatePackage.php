<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Bolt\Helpers\Arr;
use Composer\Installer;
use Silex\Application;

/**
 * Composer update package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class UpdatePackage
{
    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @param $app \Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Update packages.
     *
     * @param  $packages array Indexed array of package names to update
     * @param  $options  array [Optional] changed option set
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute(array $packages = array(), array $options = array())
    {
        /** @var $composer \Composer\Composer */
        $composer = $this->app['extend.manager']->getComposer();
        $io = $this->app['extend.manager']->getIO();
        $packageManagerOptions = $this->app['extend.manager']->getOptions();

        // Handle passed in options
        if (!$options) {
            $options = Arr::mergeRecursiveDistinct($packageManagerOptions, $options);
        } else {
            $options = $packageManagerOptions;
        }

        $install = Installer::create($io, $composer);
        $config = $composer->getConfig();
        $optimize = $config->get('optimize-autoloader');

        // Set preferred install method
        $preferSource = false; // Forces installation from package sources when possible, including VCS information.
        $preferDist = false;

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

        try {
            $install
                ->setDryRun($options['dryrun'])
                ->setVerbose($options['verbose'])
                ->setPreferSource($preferSource)
                ->setPreferDist($preferDist)
                ->setDevMode(!$options['nodev'])
                ->setDumpAutoloader(!$options['noautoloader'])
                ->setRunScripts(!$options['noscripts'])
                ->setOptimizeAutoloader($optimize)
                ->setUpdate(true)
                ->setUpdateWhitelist($packages)
                ->setWhitelistDependencies($options['withdependencies'])
                ->setIgnorePlatformRequirements($options['ignoreplatformreqs'])
                ->setPreferStable($options['preferstable'])
                ->setPreferLowest($options['preferlowest'])
                ->disablePlugins();

            return $install->run();
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->critical($msg, array('event' => 'exception', 'exception' => $e));

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
