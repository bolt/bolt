<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Bolt\Filesystem\Handler\JsonFile;
use Composer\Installer;
use Composer\Json\JsonManipulator;
use Composer\Package\Version\VersionParser;

/**
 * Composer require package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RequirePackage extends BaseAction
{
    /**
     * Require (install) a package.
     *
     * @param array $package Package names and version to require
     *                       - Format: ['name' => '', 'version' => '']
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute(array $package)
    {
        $this->getComposer();
        $io = $this->getIO();

        /** @var \Bolt\Filesystem\Handler\JsonFile $jsonFile */
        $jsonFile = $this->getOptions()->composerJson();
        $newlyCreated = !$jsonFile->exists();

        if ($newlyCreated) {
            $this->app['extend.manager.json']->update();
        }

        // Format the package array
        $package = $this->formatRequirements($package);

        // Validate requirements format
        $versionParser = new VersionParser();
        foreach ($package as $constraint) {
            $versionParser->parseConstraints($constraint);
        }

        // Get a back up of the file contents
        $composerBackup = $jsonFile->parse();

        // Update our JSON file now with a specific version.
        // This is what Composer will read, and use, internally during the process.
        // After that is complete, we'll re-save with a constraint
        $this->updateComposerJson($jsonFile, $package, false);

        // JSON file has been created/updated, if we're not installing, exit
        if ($this->getOptions()->noUpdate()) {
            return 0;
        }

        // Reload Composer config
        $composer = $this->resetComposer();

        /** @var $install \Composer\Installer */
        $install = Installer::create($io, $composer)
            ->setVerbose($this->getOptions()->verbose())
            ->setPreferSource($this->getOptions()->preferSource())
            ->setPreferDist($this->getOptions()->preferDist())
            ->setDevMode(!$this->getOptions()->updateNoDev())
            ->setOptimizeAutoloader($this->getOptions()->optimizeAutoloader())
            ->setClassMapAuthoritative($this->getOptions()->classmapAuthoritative())
            ->setUpdate($this->getOptions()->update())
            ->setUpdateWhitelist(array_keys($package))
            ->setWhitelistDependencies($this->getOptions()->updateWithDependencies())
            ->setIgnorePlatformRequirements($this->getOptions()->ignorePlatformReqs())
            ->setRunScripts(!$this->getOptions()->noScripts())
        ;

        try {
            $status = $install->run();
            if ($status !== 0) {
                if ($newlyCreated) {
                    // Installation failed, deleting JSON
                    $jsonFile->delete();
                } else {
                    // Installation failed, reverting JSON to its original content
                    $jsonFile->dump($composerBackup);
                }
            }
            // Update our JSON file now with a constraint
            $this->updateComposerJson($jsonFile, $package, true);

            return $status;
        } catch (\Exception $e) {
            // Installation failed, reverting JSON to its original content
            $jsonFile->dump($composerBackup);

            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update the JSON file.
     *
     * @param JsonFile $jsonFile
     * @param array    $package
     * @param boolean  $isPostInstall
     */
    private function updateComposerJson(JsonFile $jsonFile, array $package, $isPostInstall)
    {
        $composerJson = $jsonFile->parse();

        $sortPackages = $this->getOptions()->sortPackages();
        $requireKey = $this->getOptions()->dev() ? 'require-dev' : 'require';
        $removeKey = $this->getOptions()->dev() ? 'require' : 'require-dev';
        $baseRequirements = array_key_exists($requireKey, $composerJson) ? $composerJson[$requireKey] : [];

        if (!$this->updateFileCleanly($jsonFile, $package, $requireKey, $removeKey, $sortPackages, $isPostInstall)) {
            foreach ($package as $name => $version) {
                $baseRequirements[$name] = $version;

                if (isset($composerJson[$removeKey][$name])) {
                    unset($composerJson[$removeKey][$name]);
                }
            }

            $composerJson[$requireKey] = $baseRequirements;
            $jsonFile->dump($composerJson);
        }
    }

    /**
     * Cleanly update a Composer JSON file.
     *
     * @param JsonFile $jsonFile
     * @param array    $new
     * @param string   $requireKey
     * @param string   $removeKey
     * @param boolean  $sortPackages
     * @param boolean  $isPostInstall
     *
     * @return bool
     */
    private function updateFileCleanly(JsonFile $jsonFile, array $new, $requireKey, $removeKey, $sortPackages, $isPostInstall)
    {
        $composerJson = $jsonFile->read();
        $manipulator = new JsonManipulator($composerJson);

        foreach ($new as $package => $constraint) {
            $constraint = $isPostInstall ? $this->findBestVersionForPackage($package, $constraint) : $constraint;
            if (!$manipulator->addLink($requireKey, $package, $constraint, $sortPackages)) {
                return false;
            }
            if (!$manipulator->removeSubNode($removeKey, $package)) {
                return false;
            }
        }
        $jsonFile->put($manipulator->getContents());

        return true;
    }
}
