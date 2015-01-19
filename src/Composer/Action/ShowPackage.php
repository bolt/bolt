<?php

namespace Bolt\Composer\Action;

use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\Factory;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\ArrayRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;

/**
 * Composer show package class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class ShowPackage
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Composer\IO\BufferIO
     */
    private $io;

    /**
     * @var Composer\Composer
     */
    private $composer;

    /**
     * @param $io       Composer\IO\BufferIO
     * @param $composer Composer\Composer
     * @param $options  array
     */
    public function __construct(\Composer\IO\BufferIO $io, \Composer\Composer $composer, array $options)
    {
        $this->options = $options;
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * @param  string $target  Repository target, either: 'self', 'platform', 'installed' or 'available'
     * @param  string $package Package name to show
     * @param  string $version Package version to show
     * @return array  Array of Composer packages
     */
    public function execute($type, $package = '', $version = '')
    {
        $this->versionParser = new VersionParser();

        // init repos
        $platformRepo = new PlatformRepository();

        if ($type === 'self') {
            $package = $this->composer->getPackage();
            $repos = $installedRepo = new ArrayRepository(array($package));
        } elseif ($type === 'platform') {
            $repos = $installedRepo = $platformRepo;
        } elseif ($type === 'installed') {
            $repos = $installedRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        } elseif ($type === 'available') {
            $installedRepo = $platformRepo;
            if ($this->composer) {
                $repos = new CompositeRepository($this->composer->getRepositoryManager()->getRepositories());
            } else {
                //No composer.json found in the current directory, showing available packages from default repos
                $defaultRepos = Factory::createDefaultRepositories($this->io);
                $repos = new CompositeRepository($defaultRepos);
            }
        } elseif ($this->composer) {
            $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
            $installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
            $repos = new CompositeRepository(array_merge(array($installedRepo), $this->composer->getRepositoryManager()->getRepositories()));
        } else {
            //No composer.json found in the current directory, showing available packages from default repos
            $defaultRepos = Factory::createDefaultRepositories($this->io);
            $installedRepo = $platformRepo;
            $repos = new CompositeRepository(array_merge(array($installedRepo), $defaultRepos));
        }

        // Single package or single version
        if (!empty($package)) {
            if (is_object($package)) {
                //
                return array($package->getName() => array(
                    'package'  => $package,
                    'versions' => $package->getVersion()
                ));
            } else {
                //
                return $this->getPackage($installedRepo, $repos, $package, $version);
            }
        }

        $packages = array();

        if ($repos instanceof CompositeRepository) {
            $repos = $repos->getRepositories();
        } elseif (!is_array($repos)) {
            $repos = array($repos);
        }

        foreach ($repos as $repo) {

            if ($repo instanceof ComposerRepository && $repo->hasProviders()) {
                foreach ($repo->getProviderNames() as $name) {
                    $packages[$name] = $name;
                }
            } else {
                foreach ($repo->getPackages() as $package) {
                    if (!isset($packages[$type][$package->getName()])
                        || !is_object($packages[$type][$package->getName()])
                        || version_compare($packages[$type][$package->getName()]->getVersion(), $package->getVersion(), '<')
                    ) {
                        $packages[$package->getName()] = array(
                            'package'  => $package,
                            'versions' => $package->getVersion()
                        );
                    }
                }
            }
        }

        return $packages;
    }

    /**
     * finds a package by name and version if provided
     *
     * @param  RepositoryInterface       $installedRepo
     * @param  RepositoryInterface       $repos
     * @param  string                    $name
     * @param  string                    $version
     * @return array                     array(CompletePackageInterface, array of versions)
     * @throws \InvalidArgumentException
     */
    protected function getPackage(RepositoryInterface $installedRepo, RepositoryInterface $repos, $name, $version = null)
    {
        $name = strtolower($name);
        $constraint = null;
        if ($version) {
            $constraint = $this->versionParser->parseConstraints($version);
        }

        $policy = new DefaultPolicy();
        $pool = new Pool('dev');
        $pool->addRepository($repos);

        $matchedPackage = null;
        $versions = array();
        $matches = $pool->whatProvides($name, $constraint);
        foreach ($matches as $index => $package) {
            // skip providers/replacers
            if ($package->getName() !== $name) {
                unset($matches[$index]);
                continue;
            }

            // select an exact match if it is in the installed repo and no specific version was required
            if (null === $version && $installedRepo->hasPackage($package)) {
                $matchedPackage = $package;
            }

            $versions[$package->getPrettyVersion()] = $package->getVersion();
            $matches[$index] = $package->getId();
        }

        // select prefered package according to policy rules
        if (!$matchedPackage && $matches && $prefered = $policy->selectPreferedPackages($pool, array(), $matches)) {
            $matchedPackage = $pool->literalToPackage($prefered[0]);
        }

        // If we have package result, return them
        if ($matchedPackage) {
            return array($matchedPackage->getName() => array(
                'package'  => $matchedPackage,
                'versions' => $versions
            ));
        }
    }
}
