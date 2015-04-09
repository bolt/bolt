<?php

namespace Bolt\Composer;

use Composer\DependencyResolver\Pool;
use Composer\IO\BufferIO;
use Composer\Package\Version\VersionSelector;
use Silex\Application;

final class Factory extends PackageManager
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var \Composer\IO\BufferIO
     */
    private $io;

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\DependencyResolver\Pool
     */
    private $pool;

    /**
     * @var \Composer\Repository\CompositeRepository
     */
    private $repos;

    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @var boolean
     */
    public $downgradeSsl = false;

    /**
     * @var array
     */
    public $messages = array();

    /**
     * @param \Silex\Application $app
     * @param array              $options
     */
    public function __construct(Application $app, array $options)
    {
        $this->app = $app;
        $this->options = $options;
    }

    /**
     * Get a Composer object.
     *
     * @return \Composer\Composer
     */
    public function getComposer()
    {
        if (!$this->composer) {
            // Set working directory
            chdir($this->options['basedir']);

            // Use the factory to get a new Composer object
            try {
                $this->composer = \Composer\Factory::create($this->getIO(), $this->options['composerjson'], true);
            } catch (\Exception $e) {
                $this->app['logger.system']->critical($e->getMessage(), array('event' => 'exception', 'exception' => $e));
            }

            if ($this->downgradeSsl) {
                $this->allowSslDowngrade(true);
            }
        }

        return $this->composer;
    }

    /**
     * Get the IO object.
     *
     * @return \Composer\IO\BufferIO
     */
    public function getIO()
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
    public function resetComposer()
    {
        $this->composer = null;

        return $this->getComposer();
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
     * Set repos to allow HTTP instead of HTTPS.
     *
     * @param boolean $choice
     */
    private function allowSslDowngrade($choice)
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
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function findBestVersionForPackage($name)
    {
        // find the latest version allowed in this pool
        $versionSelector = new VersionSelector($this->getPool());
        $package = $versionSelector->findBestCandidate($name);

        if (!$package) {
            return null;
        }

        return array(
            'name'          => $name,
            'version'       => $package->getVersion(),
            'prettyversion' => $package->getPrettyVersion(),
            'package'       => $package,
            'requirever'    => $versionSelector->findRecommendedRequireVersion($package)
        );
    }

    /**
     * Return a resolver pool that contains repositories, that provide packages.
     *
     * @return \Composer\DependencyResolver\Pool
     */
    public function getPool()
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
    public function getMinimumStability()
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
