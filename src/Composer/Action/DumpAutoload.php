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
     */
    public function execute()
    {
        /** @var $composer \Composer\Composer */
        $composer = $this->getComposer();
        $installationManager = $composer->getInstallationManager();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $package = $composer->getPackage();
        $config = $composer->getConfig();

        if ($this->getOptions()->optimizeAutoloader()) {
            // Generating optimized autoload files
        } else {
            // Generating autoload files
        }

        try {
            $generator = $composer->getAutoloadGenerator();
            $generator->setDevMode(!$this->getOptions()->noDev());
            $generator->setRunScripts(!$this->getOptions()->noScripts());
            $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $this->getOptions()->optimizeAutoloader());
        } catch (\Exception $e) {
            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
