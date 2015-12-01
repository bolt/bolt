<?php
namespace Bolt\Composer\EventListener;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;

class PackageEventListener
{
    /**
     * Event handler for composer package events
     *
     * @param PackageEvent $event
     */
    public static function handle(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            return;
        }

        $extra = $package->getExtra();
        if ($package->getType() !== 'bolt-extension' || !isset($extra['bolt-assets'])) {
            return;
        }
        $packageAssets = 'vendor/' . $package->getName() . '/' . $extra['bolt-assets'];

        // Copy package assets to main web path
        $rootExtra = $event->getComposer()->getPackage()->getExtra();
        $dest = realpath($rootExtra['bolt-web-path'] . '/extensions/' . $packageAssets);

        self::mirror($packageAssets, $dest);
    }

    /**
     * Mirror a directory if the two directories don't match
     *
     * @param string $source
     * @param string $dest
     */
    public static function mirror($source, $dest)
    {
        @mkdir($dest, 0755, true);

        if (realpath($source) === realpath($dest)) {
            return;
        }

        /** @var $iterator \RecursiveIteratorIterator|\RecursiveDirectoryIterator */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            /** @var $item \SplFileInfo */
            if ($item->isDir()) {
                $new = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                if (!is_dir($new)) {
                    mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            }
        }
    }
}
