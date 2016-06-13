<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryInterface;

/**
 * Composer search package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class SearchPackage extends BaseAction
{
    /**
     * Search for packages.
     *
     * @param array   $packages Indexed array of package names to search for
     * @param boolean $onlyname True for name only search, false for full text
     *
     * @throws PackageManagerException
     *
     * @return array List of matching packages
     */
    public function execute($packages, $onlyname = true)
    {
        /** @var \Composer\Composer $composer */
        $composer = $this->getComposer();
        $io = $this->getIO();

        $platformRepo = new PlatformRepository();

        if ($composer) {
            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            $installedRepo = new CompositeRepository([$localRepo, $platformRepo]);
            $repos = new CompositeRepository(array_merge([$installedRepo], $composer->getRepositoryManager()->getRepositories()));
        } else {
            $defaultRepos = RepositoryFactory::defaultRepos($io);

            //No composer.json found in the current directory, showing packages from local repo
            $installedRepo = $platformRepo;
            $repos = new CompositeRepository(array_merge([$installedRepo], $defaultRepos));
        }

        $flags = $onlyname ? RepositoryInterface::SEARCH_NAME : RepositoryInterface::SEARCH_FULLTEXT;

        try {
            return $repos->search(implode(' ', $packages), $flags);
        } catch (\Exception $e) {
            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
