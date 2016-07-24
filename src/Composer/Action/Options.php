<?php

namespace Bolt\Composer\Action;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\JsonFile;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Composer action options class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Options extends ParameterBag
{
    /**
     * Constructor.
     *
     * @param JsonFile $composerJson
     * @param array    $composerOverrides
     * @param bool     $setDefaults
     */
    public function __construct(JsonFile $composerJson, array $composerOverrides, $setDefaults = true)
    {
        parent::__construct($composerOverrides);
        /** @var Filesystem $extensionFs */
        $extensionFs = $composerJson->getFilesystem();
        /** @var Local $adapter */
        $adapter = $extensionFs->getAdapter();
        $this->set('baseDir', $adapter->getPathPrefix());
        $this->set('composerJson', $composerJson);
    }

    /**
     * Extension base directory.
     *
     * @return string
     */
    public function baseDir()
    {
        return $this->get('baseDir');
    }

    /**
     * Location of the composer.json file.
     *
     * @return JsonFile
     */
    public function composerJson()
    {
        return $this->get('composerJson');
    }

    /**
     * Outputs the operations but will not execute anything (implicitly enables --verbose).
     *
     * Composer parameter: --dry-run
     *
     * @return bool
     */
    public function dryRun()
    {
        return $this->getBoolean('dry-run');
    }

    /**
     * Shows more details including new commits pulled in when updating packages.
     *
     * Composer parameter: --verbose
     *
     * @return bool
     */
    public function verbose()
    {
        return $this->getBoolean('verbose');
    }

    /**
     * Disables installation of require-dev packages.
     *
     * Composer parameter: --no-dev
     *
     * @return bool
     */
    public function noDev()
    {
        return $this->getBoolean('no-dev', true);
    }

    /**
     * Skips autoloader generation
     *
     * Composer parameter: --no-autoloader
     *
     * @return bool
     */
    public function noAutoloader()
    {
        return $this->getBoolean('no-autoloader');
    }

    /**
     * Skips the execution of all scripts defined in composer.json file.
     *
     * Composer parameter: --no-scripts
     *
     * @return bool
     */
    public function noScripts()
    {
        return $this->getBoolean('no-scripts');
    }

    /**
     * Add also all dependencies of whitelisted packages to the whitelist.
     *
     * Composer parameter: --with-dependencies
     *
     * @return bool
     */
    public function withDependencies()
    {
        return $this->getBoolean('with-dependencies', true);
    }

    /**
     * Ignore platform requirements (php & ext- packages)
     *
     * Composer parameter: --ignore-platform-reqs
     *
     * @return bool
     */
    public function ignorePlatformReqs()
    {
        return $this->getBoolean('ignore-platform-reqs');
    }

    /**
     * Prefer stable versions of dependencies
     *
     * Composer parameter: --prefer-stable
     *
     * @return bool
     */
    public function preferStable()
    {
        return $this->getBoolean('prefer-stable', true);
    }

    /**
     * Prefer lowest versions of dependencies.
     *
     * Composer parameter: --prefer-lowest
     *
     * @return bool
     */
    public function preferLowest()
    {
        return $this->getBoolean('prefer-lowest');
    }

    /**
     * Sorts packages when adding/updating a new dependency.
     *
     * Composer parameter: --sort-packages
     *
     * @return bool
     */
    public function sortPackages()
    {
        return $this->getBoolean('sort-packages', true);
    }

    /**
     * Forces installation from package sources when possible, including VCS information.
     *
     * Composer parameter: --prefer-source
     *
     * @return bool
     */
    public function preferSource()
    {
        return $this->getBoolean('prefer-source', false);
    }

    /**
     * Forces installation from package dist (archive) even for dev versions.
     *
     * Composer parameter: --prefer-dist
     *
     * @return bool
     */
    public function preferDist()
    {
        return $this->getBoolean('prefer-dist', true);
    }

    /**
     * Do package update as well.
     *
     * Composer parameter: --None: Bolt customisation
     *
     * @return bool
     */
    public function update()
    {
        return $this->getBoolean('update', true);
    }

    /**
     * Disables the automatic update of the dependencies
     *
     * Composer parameter: --no-update
     *
     * @return bool
     */
    public function noUpdate()
    {
        return $this->getBoolean('no-update');
    }

    /**
     * Run the dependency update with the --no-dev option.
     *
     * Composer parameter: --update-no-dev
     *
     * @return bool
     */
    public function updateNoDev()
    {
        return $this->getBoolean('update-no-dev', true);
    }

    /**
     * Allows inherited dependencies to be updated with explicit dependencies.
     *
     * Composer parameter: --update-with-dependencies
     *
     * @return bool
     */
    public function updateWithDependencies()
    {
        return $this->getBoolean('update-with-dependencies', true);
    }

    /**
     * Depending on where used:
     *   - Add requirement to require-dev
     *   - Removes a package from the require-dev section
     *   - Disables autoload-dev rules
     *
     * Composer parameter: --dev
     *
     * @return bool
     */
    public function dev()
    {
        return $this->getBoolean('dev');
    }

    /**
     * Search only in name.
     *
     * Composer parameter: --only-name
     *
     * @return bool
     */
    public function onlyName()
    {
        return $this->getBoolean('only-name', true);
    }

    /**
     * Convert PSR-0/4 autoloading to classmap to get a faster autoloader. This is recommended especially for production.
     *
     * Composer parameter: --optimize
     *
     * @return bool
     */
    public function optimize()
    {
        return $this->getBoolean('optimize', false);
    }

    /**
     * Optimizes PSR0 and PSR4 packages to be loaded with classmaps too, good for production.
     *
     * Composer parameter: --optimize-autoloader
     *
     * @return bool
     */
    public function optimizeAutoloader()
    {
        return $this->getBoolean('optimize-autoloader', false);
    }

    /**
     * Autoload classes from the classmap only. Implicitly enables --optimize-autoloader.
     *
     * Composer parameter: --classmap-authoritative
     *
     * @return bool
     */
    public function classmapAuthoritative()
    {
        return $this->getBoolean('classmap-authoritative', false);
    }
}
