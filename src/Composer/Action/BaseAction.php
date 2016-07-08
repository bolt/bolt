<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\ComposerRepository;
use Composer\Repository\ConfigurableRepositoryInterface;
use Silex\Application;

abstract class BaseAction
{
    /** @var array */
    protected $messages = [];
    /** @var \Silex\Application */
    protected $app;

    /** @var \Composer\IO\BufferIO */
    private $io;
    /** @var \Composer\Composer */
    private $composer;
    /** @var \Composer\DependencyResolver\Pool */
    private $pool;
    /** @var \Composer\Repository\CompositeRepository */
    private $repos;

    /**
     * Constructor.
     *
     * @param $app \Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Return the output from the last IO.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->io->getOutput();
    }

    /**
     * Get a single option.
     *
     * @return Options
     */
    protected function getOptions()
    {
        return $this->app['extend.action.options'];
    }

    /**
     * Get a Composer object.
     *
     * @throws \Exception
     *
     * @return \Composer\Composer
     */
    protected function getComposer()
    {
        if (!$this->composer) {
            // Set composer environment variables
            putenv('COMPOSER_HOME=' . $this->app['resources']->getPath('cache/composer'));

            // Set working directory
            chdir($this->getOptions()->baseDir());

            try {
                // Use the factory to get a new Composer object
                $this->composer = Factory::create($this->getIO(), $this->getOptions()->composerJson()->getPath(), false);
            } catch (\InvalidArgumentException $e) {
                throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
            } catch (\Exception $e) {
                $this->app['logger.system']->critical($e->getMessage(), ['event' => 'exception', 'exception' => $e]);
                throw $e;
            }

            // Add the event subscriber
            $this->composer->getEventDispatcher()->addSubscriber($this->app['extend.listener']);

            if (!$this->app['extend.manager']->useSsl()) {
                $this->setAllowSslDowngrade(true);
            }
        }

        return $this->composer;
    }

    /**
     * Get the IO object.
     *
     * @return \Composer\IO\BufferIO
     */
    protected function getIO()
    {
        return $this->app['extend.action.io'];
    }

    /**
     * Get a new Composer object.
     *
     * @return \Composer\Composer
     */
    protected function resetComposer()
    {
        $this->composer = null;

        return $this->getComposer();
    }

    /**
     * Determine if we're to force installation from package sources when
     * possible, including VCS information.
     *
     * @param string $option
     *
     * @return array
     */
    protected function getPreferedTarget($option)
    {
        $prefer = [
            'source' => false,
            'dist'   => false,
        ];

        switch ($option) {
            case 'source':
                $prefer['source'] = true;
                break;
            case 'dist':
                $prefer['dist'] = true;
                break;
            case 'auto':
            default:
                break;
        }

        return $prefer;
    }

    /**
     * Set repos to allow HTTP instead of HTTPS.
     *
     * @param boolean $choice
     */
    private function setAllowSslDowngrade($choice)
    {
        $repos = $this->getComposer()->getRepositoryManager()->getRepositories();

        /** @var ConfigurableRepositoryInterface $repo */
        foreach ($repos as $repo) {
            if (!$repo instanceof ComposerRepository) {
                continue;
            }
            $reflection = new \ReflectionClass($repo);
            $allowSslDowngrade = $reflection->getProperty('allowSslDowngrade');
            $allowSslDowngrade->setAccessible(true);
            $allowSslDowngrade->setValue($repo, $choice);
        }

        $config = $this->getComposer()->getConfig();
        $reflection = new \ReflectionClass($config);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $values = $property->getValue($config);
        $values['secure-http'] = !$choice;
        $property->setValue($config, $values);
    }

    /**
     * Given a package name, this determines the best version to use in the require key.
     *
     * This returns a version with the ^ operator prefixed when possible.
     *
     * @param string $packageName
     * @param string $targetPackageVersion
     * @param bool   $returnArray
     *
     * @throws \InvalidArgumentException
     *
     * @return PackageInterface|array
     */
    protected function findBestVersionForPackage($packageName, $targetPackageVersion = null, $returnArray = false)
    {
        $versionSelector = new VersionSelector($this->getPool());
        $package = $versionSelector->findBestCandidate($packageName, $targetPackageVersion, strtok(PHP_VERSION, '-'), $this->getComposer()->getPackage()->getStability());
        if (!$package) {
            if ($returnArray === false) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Could not find package %s at any version for your minimum-stability (%s). Check the package spelling or your minimum-stability',
                        $packageName,
                        $this->getMinimumStability()
                    )
                );
            }

            return null;
        }

        if ($returnArray === false) {
            return $versionSelector->findRecommendedRequireVersion($package);
        }

        return [
            'name'          => $packageName,
            'version'       => $package->getVersion(),
            'prettyversion' => $package->getPrettyVersion(),
            'package'       => $package,
            'requirever'    => $versionSelector->findRecommendedRequireVersion($package),
        ];
    }

    /**
     * Return a resolver pool that contains repositories, that provide packages.
     *
     * @return \Composer\DependencyResolver\Pool
     */
    protected function getPool()
    {
        if (!$this->pool) {
            $this->pool = new Pool($this->getMinimumStability());

            // Add each of our configured repos to the pool,
            // or it defaults to packagist.org
            foreach ($this->getRepos() as $repo) {
                $this->pool->addRepository($repo);
            }
        }

        return $this->pool;
    }

    /**
     * Determine the minimum requried stability.
     *
     * @return string
     */
    protected function getMinimumStability()
    {
        $stability = $this->getComposer()->getPackage()->getMinimumStability();
        if (!empty($stability)) {
            return $stability;
        }

        return 'stable';
    }

    /**
     * Get all our repos.
     *
     * @return \Composer\Repository\CompositeRepository
     */
    protected function getRepos()
    {
        if (!$this->repos) {
            $this->repos = $this->getComposer()->getRepositoryManager()->getRepositories();
        }

        return $this->repos;
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
}
