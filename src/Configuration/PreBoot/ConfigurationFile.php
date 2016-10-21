<?php

namespace Bolt\Configuration\PreBoot;

use Bolt\Exception\BootException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Pre-boot configuration set-up class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigurationFile
{
    /**
     * Check an array of named configuration files.
     *
     * @param array  $configNames
     * @param string $rootConfigDir
     * @param string $siteConfigPath
     *
     * @throws BootException
     *
     * @return null
     */
    public static function checkConfigFiles(array $configNames, $rootConfigDir, $siteConfigPath)
    {
        foreach ($configNames as $configName) {
            static::checkConfigFile($configName, $rootConfigDir, $siteConfigPath);
        }
    }

    /**
     * Check a named configuration file.
     * - If file exists, and readable, do nothing.
     * - Attempt to copy `.yml.dist` file to the working `.yml` name/location
     *
     * @param string $configName
     * @param string $rootConfigDir
     * @param string $siteConfigPath
     *
     * @throws BootException
     *
     * @return null
     */
    public static function checkConfigFile($configName, $rootConfigDir, $siteConfigPath)
    {
        $fs = new Filesystem();

        $configFileName = $configName . '.yml';
        $configFileFullPath = $siteConfigPath . '/' . $configFileName;

        $configFileDistName = $configName . '.yml.dist';
        $configFileDistNameFullPath = $rootConfigDir . '/' . $configFileDistName;

        if ($fs->exists($configFileFullPath)) {
            if (is_readable($configFileFullPath)) {
                return null;
            }

            throw new BootException(sprintf('Unable to read configuration file "%s"', $configFileName));
        }

        try {
            $fs->copy($configFileDistNameFullPath, $configFileFullPath, false);
        } catch (IOException $e) {
            throw new BootException(sprintf('Unable to create configuration file "%s"', $configFileName));
        }

        return null;
    }
}
