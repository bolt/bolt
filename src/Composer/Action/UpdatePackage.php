<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Bolt\Helpers\Arr;
use Composer\Installer;
use Silex\Application;

/**
 * Composer update package class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class UpdatePackage
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @param $app Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
        $composer = $this->app['extend.manager']->getComposer();
        $io = $this->app['extend.manager']->getIO();
        $options = $this->app['extend.manager']->getOptions();

        // Handle passed in options
        if (!is_null($options)) {
            $options = Arr::mergeRecursiveDistinct($options, $options);
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
            $this->app['logger.system']->addCritical($msg, array('event' => 'exception'));
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
