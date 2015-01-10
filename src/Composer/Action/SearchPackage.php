<?php

namespace Bolt\Composer\Action;

use Composer\Factory;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;

class SearchPackage
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
    public function __construct($io, $composer, $options)
    {
        $this->options = $options;
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * Search for packages
     *
     * @param  $packages array Indexed array of package names to search for
     * @return array List of matching packages
     */
    public function execute($packages)
    {
        $platformRepo = new PlatformRepository();

        if ($this->composer) {
            $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
            $installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
            $repos = new CompositeRepository(array_merge(array($installedRepo), $this->composer->getRepositoryManager()->getRepositories()));
        } else {
            $defaultRepos = Factory::createDefaultRepositories($this->io);

            //No composer.json found in the current directory, showing packages from local repo
            $installedRepo = $platformRepo;
            $repos = new CompositeRepository(array_merge(array($installedRepo), $defaultRepos));
        }

        $flags = $this->onlyname ? RepositoryInterface::SEARCH_NAME : RepositoryInterface::SEARCH_FULLTEXT;

        return $repos->search(implode(' ', $packages), $flags);
    }
}
