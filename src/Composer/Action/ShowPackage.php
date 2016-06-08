<?php

namespace Bolt\Composer\Action;

use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\Package\Version\VersionParser;
use Composer\Repository\ArrayRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;

/**
 * Composer show package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class ShowPackage extends BaseAction
{
    /** @var \Composer\Package\Version\VersionParser */
    private $versionParser;

    /**
     * Retrieves detailed information about a package, or lists all packages available.
     *
     * @param string  $type    Repository type, either: 'self', 'platform', 'installed' or 'available'
     * @param string  $package Package name to show
     * @param string  $version Package version to show
     * @param boolean $root    Query the Bolt parent composer install
     *
     * @return array Array of Composer packages
     */
    public function execute($type, $package = '', $version = '', $root = false)
    {
        $io = $this->getIO();

        if ($root) {
            $composerJson = $this->app['resources']->getPath('root/composer.json');
            $composer = Factory::create($io, $composerJson, true);
        } else {
            $composer = $this->getComposer();
        }

        $this->versionParser = new VersionParser();

        // Initialize repos.
        $platformRepo = new PlatformRepository();

        switch ($type) {
            case 'self':
                $package = $composer->getPackage();
                $repos = $installedRepo = new ArrayRepository([$package]);
                break;

            case 'platform':
                $repos = $platformRepo;
                $installedRepo = $platformRepo;
                break;

            case 'installed':
                $repos = $composer->getRepositoryManager()->getLocalRepository();
                $installedRepo = $repos;
                break;

            case 'available':
                $installedRepo = $platformRepo;
                if ($composer) {
                    $repos = new CompositeRepository($composer->getRepositoryManager()->getRepositories());
                } else {
                    // No composer.json found in the current directory, showing available packages from default repos.
                    $defaultRepos = RepositoryFactory::defaultRepos($io);
                    $repos = new CompositeRepository($defaultRepos);
                }
                break;

            default:
                if ($composer) {
                    $localRepo = $composer->getRepositoryManager()->getLocalRepository();
                    $installedRepo = new CompositeRepository([$localRepo, $platformRepo]);
                    $merged = array_merge([$installedRepo], $composer->getRepositoryManager()->getRepositories());
                    $repos = new CompositeRepository($merged);
                } else {
                    // No composer.json found in the current directory, showing available packages from default repos.
                    $defaultRepos = RepositoryFactory::defaultRepos($io);
                    $installedRepo = $platformRepo;
                    $repos = new CompositeRepository(array_merge([$installedRepo], $defaultRepos));
                }
        }

        // Single package or single version.
        if (!empty($package)) {
            if (is_object($package)) {
                return [
                    $package->getName() => [
                        'package'  => $package,
                        'versions' => $package->getVersion(),
                    ],
                ];
            } else {
                return $this->getPackage($installedRepo, $repos, $package, $version);
            }
        }

        $packages = [];

        if ($repos instanceof CompositeRepository) {
            $repos = $repos->getRepositories();
        } elseif (!is_array($repos)) {
            $repos = [$repos];
        }

        foreach ($repos as $repo) {
            if ($repo instanceof ComposerRepository && $repo->hasProviders()) {
                foreach ($repo->getProviderNames() as $name) {
                    $packages[$name] = $name;
                }
            } else {
                foreach ($repo->getPackages() as $package) {
                    /** @var $package \Composer\Package\PackageInterface */
                    if (!isset($packages[$type][$package->getName()])
                        || !is_object($packages[$type][$package->getName()])
                        || version_compare($packages[$type][$package->getName()]->getVersion(), $package->getVersion(), '<')
                    ) {
                        $packages[$package->getName()] = [
                            'package'  => $package,
                            'versions' => $package->getVersion(),
                        ];
                    }
                }
            }
        }

        ksort($packages);

        return $packages;
    }

    /**
     * Finds a package by name and version if provided.
     *
     * @param RepositoryInterface $installedRepo
     * @param RepositoryInterface $repos
     * @param string              $name
     * @param string|null         $version
     *
     * @throws \InvalidArgumentException
     *
     * @return array [CompletePackageInterface, array of versions]
     */
    protected function getPackage(RepositoryInterface $installedRepo, RepositoryInterface $repos, $name, $version = null)
    {
        $name = strtolower($name);
        $constraint = null;

        if ($version !== null) {
            $constraint = $this->versionParser->parseConstraints($version);
        }

        $policy = new DefaultPolicy();
        $pool = new Pool('dev');
        $pool->addRepository($repos);

        $matchedPackage = null;
        $versions = [];
        $matches = $pool->whatProvides($name, $constraint);
        foreach ($matches as $index => $package) {
            // Skip providers/replacers.
            if ($package->getName() !== $name) {
                unset($matches[$index]);
                continue;
            }

            // Select an exact match if it is in the installed repo and no specific version was required.
            if ($version === null && $installedRepo->hasPackage($package)) {
                $matchedPackage = $package;
            }

            $versions[$package->getPrettyVersion()] = $package->getVersion();
            $matches[$index] = $package->getId();
        }

        // Select prefered package according to policy rules.
        if (!$matchedPackage
            && !empty($matches)
            && $prefered = $policy->selectPreferredPackages($pool, [], $matches)
        ) {
            $matchedPackage = $pool->literalToPackage($prefered[0]);
        }

        // If we have package result, return them.
        if ($matchedPackage) {
            return [
                $matchedPackage->getName() => [
                    'package'  => $matchedPackage,
                    'versions' => $versions,
                ],
            ];
        }

        return null;
    }
}
