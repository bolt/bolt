<?php

namespace Bolt\Composer;

use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Composer\Composer;
use Composer\DependencyResolver\Pool;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Version\VersionSelector;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Exception\CurlException;
use Silex\Application;

final class Factory extends PackageManager
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
     * @var Silex\Application
     */
    private $app;

    /**
     * @var boolean
     */
    protected $downgradeSsl = false;

    /**
     * @var array
     */
    public $messages = array();

    /**
     * @param Silx\Application        $app
     * @param array                   $options
     */
    public function __construct(Application $app, array $options)
    {
        $this->app = $app;
        $this->options = $options;
    }

    /**
     * Get a Composer object
     *
     * @return Composer\Composer
     */
    public function getComposer()
    {
        if (!$this->composer) {
            // Set working directory
            chdir($this->options['basedir']);

            // Use the factory to get a new Composer object
            $this->composer = \Composer\Factory::create($this->getIO(), $this->options['composerjson'], true);

            if ($this->downgradeSsl) {
                $this->allowSslDowngrade(true);
            }
        }

        return $this->composer;
    }

    /**
     * Get the IOInterface object
     *
     * @return Composer\IO\IOInterface
     */
    public function getIO()
    {
        if (!$this->io) {
            $this->io = new BufferIO();
        }

        return $this->io;
    }

    /**
     * Get a new Composer object
     *
     * @return Bolt\Composer\Factory
     */
    protected function resetComposer()
    {
        $this->composer = null;

        return $this->getComposer();
    }

    /**
     * Return the output from the last IO
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->io->getOutput();
    }

    /**
     * Set repos to allow HTTP instead of HTTPS
     *
     * @param boolean $choice
     */
    private function allowSslDowngrade($choice)
    {
        $repos = $this->composer->getRepositoryManager()->getRepositories();

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
     * @param  string                    $name
     * @return array
     * @throws \InvalidArgumentException
     */
    public function findBestVersionForPackage($name)
    {
        // find the latest version allowed in this pool
        $versionSelector = new VersionSelector($this->getPool());
        $package = $versionSelector->findBestCandidate($name);

        if (!$package) {
            return;
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
     * Return a resolver pool that contains repositories, that provide packages
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
     * Determine the minimum requried stability
     *
     * @return string
     */
    protected function getMinimumStability()
    {
        $stability = $this->composer->getPackage()->getMinimumStability();
        if (!empty($stability)) {
            return $stability;
        }

        return 'stable';
    }

    /**
     * Get all our repos
     *
     * @return \Composer\Repository\CompositeRepository
     */
    protected function getRepos()
    {
        if (!$this->repos) {
            $this->repos = $this->composer->getRepositoryManager()->getRepositories();
        }

        return $this->repos;
    }
}
