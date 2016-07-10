<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;

/**
 * Checks for installable, or upgradeable packages.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class CheckPackage extends BaseAction
{
    /**
     * Run a check for package(s).
     *
     * @throws PackageManagerException
     *
     * @return array
     */
    public function execute()
    {
        $packages = ['updates' => [], 'installs' => []];

        // Get known installed packages
        $rootPackage = $this->app['extend.action']['show']->execute('installed');

        /** @var \Bolt\Filesystem\Handler\JsonFile $jsonFile */
        $jsonFile = $this->getOptions()->composerJson();

        // Get the packages that a set as "required" in the JSON file
        $json = $jsonFile->parse();
        $jsonRequires = isset($json['require']) ? (array) $json['require'] : [];

        /**
         * @var string $packageName
         * @var string $versionConstraint
         */
        foreach ($jsonRequires as $packageName => $versionConstraint) {
            try {
                $remote = $this->findBestVersionForPackage($packageName, $versionConstraint, true);
            } catch (\Exception $e) {
                $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
                $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

                throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
            }
            if (!is_array($remote)) {
                continue;
            }

            if (array_key_exists($packageName, $rootPackage)) {
                $rootVer = isset($rootPackage[$packageName]['versions']) ? $rootPackage[$packageName]['versions'] : false;
                if ($rootVer && $rootVer !== $remote['package']->getVersion()) {
                    $packages['updates'][] = $remote;
                }
            } else {
                $packages['installs'][] = $remote;
                unset($jsonRequires[$packageName]);
            }
        }

        return $packages;
    }
}
