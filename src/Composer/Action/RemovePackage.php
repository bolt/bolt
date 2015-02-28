<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Config\JsonConfigSource;
use Composer\Installer;
use Composer\Json\JsonFile;
use Silex\Application;

/**
 * Composer remove package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RemovePackage
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
     * Remove packages from the root install.
     *
     * @param  $packages array Indexed array of package names to remove
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute(array $packages)
    {
        if (empty($packages)) {
            throw new PackageManagerException('No package specified for removal');
        }

        $io = $this->app['extend.manager']->getIO();
        $options = $this->app['extend.manager']->getOptions();

        $jsonFile = new JsonFile($options['composerjson']);
        $composerDefinition = $jsonFile->read();
        $composerBackup = file_get_contents($jsonFile->getPath());

        $json = new JsonConfigSource($jsonFile);

        $type = $options['dev'] ? 'require-dev' : 'require';

        // Remove packages from JSON
        foreach ($packages as $package) {
            if (isset($composerDefinition[$type][$package])) {
                $json->removeLink($type, $package);
            }
        }

        // Reload Composer config
        $composer = $this->app['extend.manager']->getFactory()->resetComposer();

        $install = Installer::create($io, $composer);

        try {
            $install
                ->setVerbose($options['verbose'])
                ->setDevMode(!$options['updatenodev'])
                ->setUpdate(true)
                ->setUpdateWhitelist($packages)
                ->setWhitelistDependencies($options['updatewithdependencies'])
                ->setIgnorePlatformRequirements($options['ignoreplatformreqs']);

            $status = $install->run();

            if ($status !== 0) {
                // Write out old JSON file
                file_put_contents($jsonFile->getPath(), $composerBackup);
            }
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->critical($msg, array('event' => 'exception', 'exception' => $e));

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }

        return $status;
    }
}
