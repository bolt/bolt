<?php

namespace Bolt\Composer\Action;

use Bolt\Filesystem\Handler\JsonFile;

/**
 * Composer action options class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Options
{
    /** @var  string */
    protected $baseDir;
    /** @var  JsonFile */
    protected $composerJson;
    /** @var  bool */
    protected $dryRun;
    /** @var  bool */
    protected $verbose;
    /** @var  bool */
    protected $noDev;
    /** @var  bool */
    protected $noAutoloader;
    /** @var  bool */
    protected $noScripts;
    /** @var  bool */
    protected $withDependencies;
    /** @var  bool */
    protected $ignorePlatformReqs;
    /** @var  bool */
    protected $preferStable;
    /** @var  bool */
    protected $preferLowest;
    /** @var  bool */
    protected $sortPackages;
    /** @var  bool */
    protected $preferSource;
    /** @var  bool */
    protected $preferDist;
    /** @var  bool */
    protected $update;
    /** @var  bool */
    protected $noUpdate;
    /** @var  bool */
    protected $updateNoDev;
    /** @var  bool */
    protected $updateWithDependencies;
    /** @var  bool */
    protected $dev;
    /** @var  bool */
    protected $onlyName;
    /** @var  bool */
    protected $optimize;
    /** @var  bool */
    protected $optimizeAutoloader;
    /** @var  bool */
    protected $classmapAuthoritative;

    /**
     * Constructor.
     *
     * @param JsonFile $composerJson
     * @param bool     $setDefaults
     */
    public function __construct(JsonFile $composerJson, $setDefaults = true)
    {
        $this->composerJson = $composerJson;
        $this->baseDir = $composerJson->getFilesystem()->getAdapter()->getPathPrefix();

        if ($setDefaults) {
            $this->setDefaults();
        }
    }

    /**
     * Set a value.
     *
     * @param string $name
     * @param bool   $value
     */
    public function set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Extension base directory.
     *
     * @return string
     */
    public function baseDir()
    {
        return $this->baseDir;
    }

    /**
     * Location of the composer.json file.
     *
     * @return JsonFile
     */
    public function composerJson()
    {
        return $this->composerJson;
    }

    /**
     * Outputs the operations but will not execute anything (implicitly enables --verbose).
     *
     * Composer parameter: --dry-run
     *
     * @return bool|null
     */
    public function dryRun()
    {
        return $this->dryRun;
    }

    /**
     * Shows more details including new commits pulled in when updating packages.
     *
     * Composer parameter: --verbose
     *
     * @return bool|null
     */
    public function verbose()
    {
        return $this->verbose;
    }

    /**
     * Disables installation of require-dev packages.
     *
     * Composer parameter: --no-dev
     *
     * @return bool|null
     */
    public function noDev()
    {
        return $this->noDev;
    }

    /**
     * Skips autoloader generation
     *
     * Composer parameter: --no-autoloader
     *
     * @return bool|null
     */
    public function noAutoloader()
    {
        return $this->noAutoloader;
    }

    /**
     * Skips the execution of all scripts defined in composer.json file.
     *
     * Composer parameter: --no-scripts
     *
     * @return bool|null
     */
    public function noScripts()
    {
        return $this->noScripts;
    }

    /**
     * Add also all dependencies of whitelisted packages to the whitelist.
     *
     * Composer parameter: --with-dependencies
     *
     * @return bool|null
     */
    public function withDependencies()
    {
        return $this->withDependencies;
    }

    /**
     * Ignore platform requirements (php & ext- packages)
     *
     * Composer parameter: --ignore-platform-reqs
     *
     * @return bool|null
     */
    public function ignorePlatformReqs()
    {
        return $this->ignorePlatformReqs;
    }

    /**
     * Prefer stable versions of dependencies
     *
     * Composer parameter: --prefer-stable
     *
     * @return bool|null
     */
    public function preferStable()
    {
        return $this->preferStable;
    }

    /**
     * Prefer lowest versions of dependencies.
     *
     * Composer parameter: --prefer-lowest
     *
     * @return bool|null
     */
    public function preferLowest()
    {
        return $this->preferLowest;
    }

    /**
     * Sorts packages when adding/updating a new dependency.
     *
     * Composer parameter: --sort-packages
     *
     * @return bool|null
     */
    public function sortPackages()
    {
        return $this->sortPackages;
    }

    /**
     * Forces installation from package sources when possible, including VCS information.
     *
     * Composer parameter: --prefer-source
     *
     * @return bool|null
     */
    public function preferSource()
    {
        return $this->preferSource;
    }

    /**
     * Forces installation from package dist (archive) even for dev versions.
     *
     * Composer parameter: --prefer-dist
     *
     * @return bool|null
     */
    public function preferDist()
    {
        return $this->preferDist;
    }

    /**
     * Do package update as well.
     *
     * Composer parameter: --None: Bolt customisation
     *
     * @return bool|null
     */
    public function update()
    {
        return $this->update;
    }

    /**
     * Disables the automatic update of the dependencies
     *
     * Composer parameter: --no-update
     *
     * @return bool|null
     */
    public function noUpdate()
    {
        return $this->noUpdate;
    }

    /**
     * Run the dependency update with the --no-dev option.
     *
     * Composer parameter: --update-no-dev
     *
     * @return bool|null
     */
    public function updateNoDev()
    {
        return $this->updateNoDev;
    }

    /**
     * Allows inherited dependencies to be updated with explicit dependencies.
     *
     * Composer parameter: --update-with-dependencies
     *
     * @return bool|null
     */
    public function updateWithDependencies()
    {
        return $this->updateWithDependencies;
    }

    /**
     * Depending on where used:
     *   - Add requirement to require-dev
     *   - Removes a package from the require-dev section
     *   - Disables autoload-dev rules
     *
     * Composer parameter: --dev
     *
     * @return bool|null
     */
    public function dev()
    {
        return $this->dev;
    }

    /**
     * Search only in name.
     *
     * Composer parameter: --only-name
     *
     * @return bool|null
     */
    public function onlyName()
    {
        return $this->onlyName;
    }

    /**
     * Convert PSR-0/4 autoloading to classmap to get a faster autoloader. This is recommended especially for production.
     *
     * Composer parameter: --optimize
     *
     * @return bool|null
     */
    public function optimize()
    {
        return $this->optimize;
    }

    /**
     * Optimizes PSR0 and PSR4 packages to be loaded with classmaps too, good for production.
     *
     * Composer parameter: --optimize-autoloader
     *
     * @return bool|null
     */
    public function optimizeAutoloader()
    {
        return $this->optimizeAutoloader;
    }

    /**
     * Autoload classes from the classmap only. Implicitly enables --optimize-autoloader.
     *
     * Composer parameter: --classmap-authoritative
     *
     * @return bool|null
     */
    public function classmapAuthoritative()
    {
        return $this->classmapAuthoritative;
    }

    /**
     * Set default parameter options.
     */
    private function setDefaults()
    {
        $this->noDev = true;
        $this->onlyName = true;
        $this->optimizeAutoloader = true;
        $this->classmapAuthoritative = true;
        $this->preferDist = true;
        $this->preferSource = false;
        $this->sortPackages = true;
        $this->updateNoDev = true;
        $this->update = true;
        $this->updateWithDependencies = true;
        $this->withDependencies = true;
    }
}
