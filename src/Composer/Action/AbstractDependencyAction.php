<?php

namespace Bolt\Composer\Action;

use Bolt\Composer\Package\Dependency;
use Composer\DependencyResolver\Pool;
use Composer\Package\BasePackage;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Repository\ArrayRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Semver\VersionParser;

/**
 * Abstract class for mapping Composer dependency relationships.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractDependencyAction extends BaseAction
{
    /** @var bool Whether to invert matching process (why-not vs why behaviour) */
    protected $inverted = false;
    /** @var bool */
    protected $recursive = false;

    /**
     * Execute.
     *
     * @param string $packageName    Package to inspect.
     * @param string $textConstraint Optional version constraint
     * @param bool   $onlyLocal
     *
     * @return Dependency[]|null
     */
    public function execute($packageName, $textConstraint = '*', $onlyLocal = true)
    {
        // Find packages that are or provide the requested package first
        $pool = $this->getRequiredPool($packageName, $textConstraint, $onlyLocal);
        $packages = $pool->whatProvides($packageName);
        if (empty($packages)) {
            // sprintf('Could not find package "%s" in your project', $packageName),
            return null;
        }

        // Get the needle stack
        $needles = $this->getNeedles($packageName, $packages, $this->inverted);

        // Parse constraint if one was supplied
        $constraint = null;
        if ($textConstraint !== '*') {
            $versionParser = new VersionParser();
            $constraint = $versionParser->parseConstraints($textConstraint);
        }
        $extra = $constraint !== null ? sprintf(' in versions %s "%s"', $this->inverted ? 'not matching' : 'matching', $textConstraint) : '';

        // Resolve dependencies
        /** @var InstalledFilesystemRepository $repository */
        $repository = $this->getComposer()->getRepositoryManager()->getLocalRepository();
        $results = $repository->getDependents($needles, $constraint, $this->inverted, $this->recursive);
        if (empty($results)) {
            // sprintf('There is no installed package depending on "%s"%s', $packageName, $extra),
            return null;
        }

        // sprintf('The following packages are involved in the dependency on "%s"%s', $packageName, $extra),
        return $this->getDependencies($results);
    }

    /**
     * If the version we ask for is not installed then we need to locate it in
     * remote repos and add it.
     *
     * This is needed for why-not to resolve conflicts from an uninstalled
     * version against installed packages.
     *
     * @param string $packageName    Package to inspect.
     * @param string $textConstraint Optional version constraint
     * @param bool   $onlyLocal
     *
     * @return Pool
     */
    private function getRequiredPool($packageName, $textConstraint, $onlyLocal)
    {
        if ($onlyLocal === false) {
            return $this->getPool();
        }
        $composer = $this->getComposer();
        $pool = new Pool();

        // Prepare repositories and set up a pool
        $platformOverrides = $composer->getConfig()->get('platform') ?: [];
        /** @var BasePackage $rootPackage */
        $rootPackage = $composer->getPackage();
        if ($rootPackage->getRepository() === null) {
            $packageRepo = new ArrayRepository([$composer->getPackage()]);

            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            $platformRepo = new PlatformRepository([], $platformOverrides);
            $compositeRepository = new CompositeRepository([$packageRepo, $localRepo, $platformRepo]);

            $pool->addRepository($compositeRepository);

            $defaultRepos = new CompositeRepository(RepositoryFactory::defaultRepos($this->getIO()));
            $match = $defaultRepos->findPackage($packageName, $textConstraint);
            if ($match) {
                $compositeRepository->addRepository(new ArrayRepository([clone $match]));
            }
        }

        return $pool;
    }

    /**
     * Return a needle stack of Links depending on why/why-not.
     *
     * @param string             $packageName
     * @param PackageInterface[] $packages
     * @param bool               $inverted
     *                                        - Prohibits = true
     *                                        - Depends   = false
     *
     * @return Link[]
     */
    private function getNeedles($packageName, $packages, $inverted)
    {
        $needles = [$packageName];
        if ($inverted === false) {
            return $needles;
        }

        // Include replaced packages for inverted lookups as they are then the actual starting point to consider
        $mapCb = function (Link $link) {
            return $link->getTarget();
        };
        foreach ($packages as $package) {
            $needles = array_merge(
                $needles,
                array_map($mapCb, $package->getReplaces())
            );
        }

        return $needles;
    }

    /**
     * Assembles and prints a bottom-up table of the dependencies.
     *
     * @param array $results
     *
     * @return array
     */
    private function getDependencies(array $results)
    {
        $dependencies = [];
        $doubles = [];

        foreach ($results as $name => $result) {
            $type = $this->recursive ? Dependency::PROHIBITS : Dependency::DEPENDS;
            $dependency = Dependency::create($name, $type, $result);
            $unique = (string) $dependency->getLink();
            if (isset($doubles[$unique])) {
                continue;
            }
            $doubles[$unique] = true;
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }
}
