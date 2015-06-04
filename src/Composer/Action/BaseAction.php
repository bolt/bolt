<?php

namespace Bolt\Composer\Action;

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\Version\VersionSelector;
use Psr\Log\LoggerInterface;
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
    /** @var array */
    private $options;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

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
     * Get a single option.
     *
     * @param string $key
     *
     * @return string|boolean|null
     */
    protected function getOption($key)
    {
        return $this->app['extend.action.options'][$key];
    }

    /**
     * Get a Composer object.
     *
     * @return \Composer\Composer
     */
    protected function getComposer()
    {
        if (!$this->composer) {
            // Set working directory
            chdir($this->getOption('basedir'));

            // Use the factory to get a new Composer object
            try {
                $this->composer = Factory::create($this->getIO(), $this->getOption('composerjson'), true);

                if (!$this->app['extend.manager']->useSsl()) {
                    $this->setAllowSslDowngrade(true);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage(), ['event' => 'exception', 'exception' => $e]);
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
        if (!$this->io) {
            $this->io = new BufferIO();
        }

        return $this->io;
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
     * Return the output from the last IO.
     *
     * @return string
     */
    protected function getOutput()
    {
        return $this->io->getOutput();
    }

    /**
     * Set repos to allow HTTP instead of HTTPS.
     *
     * @param boolean $choice
     */
    private function setAllowSslDowngrade($choice)
    {
        $repos = $this->getComposer()->getRepositoryManager()->getRepositories();

        foreach ($repos as $repo) {
            $reflection = new \ReflectionClass($repo);
            $allowSslDowngrade = $reflection->getProperty('allowSslDowngrade');
            $allowSslDowngrade->setAccessible($choice);
            $allowSslDowngrade->setValue($repo, $choice);
        }
    }

    /**
     * Given a package name, this determines the best version to use in the require key.
     *
     * This returns a version with the ~ operator prefixed when possible.
     *
     * @param string $name
     *
     * @return array
     */
    protected function findBestVersionForPackage($name)
    {
        // find the latest version allowed in this pool
        $versionSelector = new VersionSelector($this->getPool());
        $package = $versionSelector->findBestCandidate($name);

        if (!$package) {
            return null;
        }

        return [
            'name'          => $name,
            'version'       => $package->getVersion(),
            'prettyversion' => $package->getPrettyVersion(),
            'package'       => $package,
            'requirever'    => $versionSelector->findRecommendedRequireVersion($package)
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
}
