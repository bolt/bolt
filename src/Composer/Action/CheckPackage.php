<?php

namespace Bolt\Composer\Action;

use Composer\Json\JsonFile;
use Silex\Application;

/**
 * Checks for installable, or upgradeable packages.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class CheckPackage
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
     * Run a check for package(s).
     *
     * @return array
     */
    public function execute()
    {
        $packages = array('updates' => array(), 'installs' => array());

        // Get known installed packages
        $rootpack = $this->app['extend.manager']->showPackage('installed');

        // Get the packages that a set as "required" in the JSON file
        $file = new JsonFile($this->app['extend.manager']->getOption('composerjson'));
        $json = $file->read();
        $jsonpack = $json['require'];

        // Find the packages that are NOT part of the root install yet and mark
        // them as pending installs
        if (!empty($jsonpack)) {
            foreach ($jsonpack as $package => $packageInfo) {
                if (!array_key_exists($package, $rootpack)) {
                    $remote = $this->app['extend.manager']->getFactory()->findBestVersionForPackage($package);

                    // If a 'best' version is found, and there is a version mismatch then
                    // propose as an update. Making the assumption that Composer isn't
                    // going to offer us an older version.
                    if (is_array($remote)) {
                        $packages['installs'][] = $remote;
                    }
                }
            }
        }

        // For installed packages, see if there is a valid update
        foreach ($rootpack as $package => $data) {
            $remote = $this->app['extend.manager']->getFactory()->findBestVersionForPackage($package);

            // If a 'best' version is found, and there is a version mismatch then
            // propose as an update. Making the assumption that Composer isn't
            // going to offer us an older version.
            if (is_array($remote) && ($remote['package']->getVersion() != $data['package']->getVersion())) {
                $packages['updates'][] = $remote;
            }
        }

        return $packages;
    }
}
