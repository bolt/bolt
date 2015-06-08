<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;

/**
 * Composer require package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RequirePackage extends BaseAction
{
    /** @var \Composer\Package\Version\VersionSelector */
    private $versionSelector;
    /** @var \Composer\Repository\RepositoryInterface */
    private $repos;

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
        /** @var $composer \Composer\Composer */
        $composer = $this->getComposer();
        $io = $this->getIO();

        $file = $this->getOption('composerjson');

        $newlyCreated = !file_exists($file);

        if (!file_exists($file) && !file_put_contents($file, "{\n}\n")) {
            // JSON could not be created
            return 1;
        }
        if (!is_readable($file)) {
            // JSON is not readable
            return 1;
        }
        if (!is_writable($file)) {
            // JSON is not writable
            return 1;
        }

        // Get the Composer repos
        $repos = $composer->getRepositoryManager()->getRepositories();

        $this->repos = new CompositeRepository(
            array_merge([new PlatformRepository()], $repos)
        );

        // Format the package array
        $package = $this->formatRequirements($package);

        // Validate requirements format
        $versionParser = new VersionParser();
        foreach ($package as $constraint) {
            $versionParser->parseConstraints($constraint);
        }

        // Get the JSON object
        $json = new JsonFile($this->getOption('composerjson'));

        // Update our JSON file with the selected version until we reset Composer
        $composerBackup = $this->updateComposerJson($json, $package, false);

        // Reload Composer config
        $composer = $this->resetComposer();

        // Update our JSON file now with a contraint
        $this->updateComposerJson($json, $package, true);

        // JSON file has been created/updated, if we're not installing, exit
        if ($this->getOption('noupdate')) {
            return 0;
        }

        /** @var $install \Composer\Installer */
        $install = Installer::create($io, $composer);

        try {
            $install
                ->setVerbose($this->getOption('verbose'))
                ->setPreferSource($this->getOption('prefersource'))
                ->setPreferDist($this->getOption('preferdist'))
                ->setDevMode(!$this->getOption('updatenodev'))
                ->setUpdate($this->getOption('update'))
                ->setUpdateWhitelist(array_keys($package))
                ->setWhitelistDependencies($this->getOption('updatewithdependencies'))
                ->setIgnorePlatformRequirements($this->getOption('ignoreplatformreqs'));

            $status = $install->run();
            if ($status !== 0) {
                if ($newlyCreated) {
                    // Installation failed, deleting JSON
                    unlink($json->getPath());
                } else {
                    // Installation failed, reverting JSON to its original content
                    file_put_contents($json->getPath(), $composerBackup);
                }
            }

            return $status;
        } catch (\Exception $e) {
            // Installation failed, reverting JSON to its original content
            file_put_contents($json->getPath(), $composerBackup);

            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update the JSON file.
     *
     * @param JsonFile $json
     * @param array    $package
     * @param boolean  $postreset
     *
     * @return string A back up of the current JSON file
     */
    private function updateComposerJson(JsonFile $json, array $package, $postreset)
    {
        $composerDefinition = $json->read();
        $composerBackup = file_get_contents($json->getPath());

        $sortPackages = $this->getOption('sortpackages');
        $requireKey = $this->getOption('dev') ? 'require-dev' : 'require';
        $removeKey = $this->getOption('dev') ? 'require' : 'require-dev';
        $baseRequirements = array_key_exists($requireKey, $composerDefinition) ? $composerDefinition[$requireKey] : [];

        if (!$this->updateFileCleanly($json, $package, $requireKey, $removeKey, $sortPackages, $postreset)) {
            foreach ($package as $name => $version) {
                $baseRequirements[$name] = $version;

                if (isset($composerDefinition[$removeKey][$name])) {
                    unset($composerDefinition[$removeKey][$name]);
                }
            }

            $composerDefinition[$requireKey] = $baseRequirements;
            $json->write($composerDefinition);
        }

        return $composerBackup;
    }

    /**
     * Cleanly update a Composer JSON file.
     *
     * @param JsonFile $json
     * @param array    $new
     * @param string   $requireKey
     * @param string   $removeKey
     * @param boolean  $sortPackages
     * @param boolean  $postreset
     *
     * @return boolean
     */
    private function updateFileCleanly(JsonFile $json, array $new, $requireKey, $removeKey, $sortPackages, $postreset)
    {
        $contents = file_get_contents($json->getPath());

        $manipulator = new JsonManipulator($contents);

        foreach ($new as $package => $constraint) {
            if ($postreset) {
                $constraint = $this->findBestVersionForPackage($package);
            }

            if (!$manipulator->addLink($requireKey, $package, $constraint, $sortPackages)) {
                return false;
            }
            if (!$manipulator->removeSubNode($removeKey, $package)) {
                return false;
            }
        }

        file_put_contents($json->getPath(), $manipulator->getContents());

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
