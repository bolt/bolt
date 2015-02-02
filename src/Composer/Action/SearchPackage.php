<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Factory;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Silex\Application;

/**
 * Composer search package class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class SearchPackage
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @param $app Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Search for packages
     *
     * @param  $packages array Indexed array of package names to search for
     * @return array List of matching packages
     */
    public function execute($packages)
    {
        $composer = $this->app['extend.manager']->getComposer();
        $io = $this->app['extend.manager']->getIO();

        $platformRepo = new PlatformRepository();

        if ($composer) {
            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            $installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
            $repos = new CompositeRepository(array_merge(array($installedRepo), $composer->getRepositoryManager()->getRepositories()));
        } else {
            $defaultRepos = Factory::createDefaultRepositories($io);

            //No composer.json found in the current directory, showing packages from local repo
            $installedRepo = $platformRepo;
            $repos = new CompositeRepository(array_merge(array($installedRepo), $defaultRepos));
        }

        $flags = $this->onlyname ? RepositoryInterface::SEARCH_NAME : RepositoryInterface::SEARCH_FULLTEXT;

        try {
            return $repos->search(implode(' ', $packages), $flags);
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->addCritical($msg, array('event' => 'exception'));
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
