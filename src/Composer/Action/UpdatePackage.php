<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Bolt\Helpers\Arr;
use Composer\Installer;

/**
 * Composer update package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class UpdatePackage extends BaseAction
{
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
    public function execute(array $packages = [], array $options = [])
    {
        /** @var $composer \Composer\Composer */
        $composer = $this->getComposer();
        $io = $this->getIO();
        $packageManagerOptions = $this->app['extend.action.options'];

        // Handle passed in options
        if (!empty($options)) {
            $options = Arr::mergeRecursiveDistinct($packageManagerOptions, $options);
        } else {
            $options = $packageManagerOptions;
        }

        $install = Installer::create($io, $composer);
        $config = $composer->getConfig();
        $optimize = $config->get('optimize-autoloader');

        // Set preferred install method
        $prefer = $this->getPreferedTarget($config->get('preferred-install'));

        try {
            $install
                ->setDryRun($options['dryrun'])
                ->setVerbose($options['verbose'])
                ->setPreferSource($prefer['source'])
                ->setPreferDist($prefer['dist'])
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
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
