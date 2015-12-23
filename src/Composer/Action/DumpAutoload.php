<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;

/**
 * Composer autoloader creation class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class DumpAutoload extends BaseAction
{
    /**
     * Dump autoloaders.
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute()
    {
        /** @var $composer \Composer\Composer */
        $composer = $this->getComposer();
        $installationManager = $composer->getInstallationManager();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $package = $composer->getPackage();
        $config = $composer->getConfig();

        $optimize = $this->getOptions()->optimize() || $this->getOptions()->optimizeAutoloader();
        $authoritative = $this->getOptions()->classmapAuthoritative() || $config->get('classmap-authoritative');

        if ($optimize || $authoritative) {
            $this->getIO()->writeError('<info>Generating optimized autoload files</info>');
        } else {
            $this->getIO()->writeError('<info>Generating autoload files</info>');
        }

        $generator = $composer->getAutoloadGenerator();
        $generator->setDevMode(!$this->getOptions()->noDev());
        $generator->setClassMapAuthoritative($authoritative);
        $generator->setRunScripts(!$this->getOptions()->noScripts());

        try {
            $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $optimize);
        } catch (\Exception $e) {
            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }

        return 0;
    }
}
