<?php

namespace Bolt\Composer\Script;

use Bolt\Bootstrap;
use Bolt\Configuration\PathResolver;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Silex\Application;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Syncs directories from core to user project.
 *
 * @internal
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class DirectorySyncer
{
    /** @var PathResolver */
    private $userResolver;
    /** @var PathResolver */
    private $boltResolver;
    /** @var IOInterface */
    private $io;
    /** @var Options */
    private $options;
    /** @var Filesystem */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param PathResolver $userResolver
     * @param PathResolver $boltResolver
     * @param IOInterface  $io
     * @param Options|null $options
     * @param Filesystem   $filesystem
     */
    public function __construct(
        PathResolver $userResolver,
        PathResolver $boltResolver,
        IOInterface $io,
        Options $options = null,
        Filesystem $filesystem = null
    ) {
        $this->userResolver = $userResolver;
        $this->boltResolver = $boltResolver;
        $this->io = $io;
        $this->options = $options ?: new Options();
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Create from Composer Event.
     *
     * @param Event $event
     *
     * @return DirectorySyncer
     */
    public static function fromEvent(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $boltDir = $vendorDir . '/bolt/bolt';

        if (file_exists($boltDir)) {
            /**
             * Use bootstrap.php to ensure autoloader and correct root path is used.
             *
             * @var Application $userApp
             */
            $userApp = require $boltDir . '/app/bootstrap.php';
            $userResolver = $userApp['path_resolver'];

            $app = Bootstrap::run($boltDir);
            $boltResolver = $app['path_resolver'];
        } else {
            // If git install, both resolvers are the same.
            // This shouldn't be needed, and ends up doing nothing.
            // But better that than having the code break.
            $app = Bootstrap::run(__DIR__ . '/../../..');
            $userResolver = $boltResolver = $app['path_resolver'];
        }
        $boltResolver->define('vendor', $vendorDir);

        return new static($userResolver, $boltResolver, $event->getIO(), Options::fromEvent($event));
    }

    /**
     * @param string $srcName     The source path alias in PathResolver to sync from
     * @param string $targetName  The target path alias in PathResolver to sync to
     * @param bool   $delete      Whether to delete files that are not in the source directory
     * @param array  $onlySubDirs Only sync these sub dirs if given
     */
    public function sync($srcName, $targetName, $delete = false, $onlySubDirs = [])
    {
        $origin = $this->boltResolver->resolve($srcName);
        $target = $this->userResolver->resolve($targetName);

        if ($origin === $target) {
            return;
        }

        $this->io->writeError("Installing <info>$targetName</info> to <info>$target</info>");

        $old = umask(0777 - $this->options->getDirMode());
        try {
            if ($onlySubDirs) {
                foreach ($onlySubDirs as $subDir) {
                    $this->mirror($origin . '/' . $subDir, $target . '/' . $subDir, $delete);
                }
            } else {
                $this->mirror($origin, $target, $delete);
            }
        } finally {
            umask($old);
        }
    }

    /**
     * Helper to make mirror calls shorter.
     *
     * @param string $origin
     * @param string $target
     * @param bool   $delete
     */
    private function mirror($origin, $target, $delete = false)
    {
        $this->filesystem->mirror($origin, $target, null, ['override' => true, 'delete' => $delete]);
    }
}
