<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Bolt\Filesystem\Handler\JsonFile;
use Composer\Installer;
use Composer\Json\JsonManipulator;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;

/**
 * Composer require package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RequirePackage extends BaseAction
{
    /** @var \Composer\Package\Version\VersionSelector */
    private $versionSelector;

    /**
     * Require (install) a package.
     *
     * @param  $package       array Package names and version to require
     *                        - Format: ['name' => '', 'version' => '']
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute(array $package)
    {
        $this->versionSelector = new VersionSelector($this->getPool());
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

        // Update our JSON file now with a contraint
        $this->updateComposerJson($jsonFile, $package);

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
     */
    private function updateComposerJson(JsonFile $jsonFile, array $package)
    {
        $composerJson = $jsonFile->parse();

        $sortPackages = $this->getOptions()->sortPackages();
        $requireKey = $this->getOptions()->dev() ? 'require-dev' : 'require';
        $removeKey = $this->getOptions()->dev() ? 'require' : 'require-dev';
        $baseRequirements = array_key_exists($requireKey, $composerJson) ? $composerJson[$requireKey] : [];

        if (!$this->updateFileCleanly($jsonFile, $package, $requireKey, $removeKey, $sortPackages)) {
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
     *
     * @return boolean
     */
    private function updateFileCleanly(JsonFile $jsonFile, array $new, $requireKey, $removeKey, $sortPackages)
    {
        $composerJson = $jsonFile->read();
        $manipulator = new JsonManipulator($composerJson);

        foreach ($new as $package => $constraint) {
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

    /**
     * @param array $packages
     *
     * @return array
     */
    protected function formatRequirements(array $packages)
    {
        $requires = [];
        $packages = $this->normalizeRequirements($packages);
        foreach ($packages as $package) {
            $requires[$package['name']] = $package['version'];
        }

        return $requires;
    }

    /**
     * Parses a name/version pairs and returns an array of pairs.
     *
     * @param array $packages a set of package/version pairs separated by ":", "=" or " "
     *
     * @return array[] An array of arrays containing a name and (if provided) a version
     */
    protected function normalizeRequirements(array $packages)
    {
        $parser = new VersionParser();

        return $parser->parseNameVersionPairs($packages);
    }

    /**
     * Given a package name, this determines the best version to use in the require key.
     *
     * This returns a version with the ~ operator prefixed when possible.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function findBestVersionForPackage($name)
    {
        $package = $this->versionSelector->findBestCandidate($name);

        if (!$package) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not find package %s at any version for your minimum-stability (%s). Check the package spelling or your minimum-stability',
                    $name,
                    $this->getMinimumStability()
                )
            );
        }

        return $this->versionSelector->findRecommendedRequireVersion($package);
    }
}
