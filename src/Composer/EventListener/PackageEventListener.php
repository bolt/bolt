<?php

namespace Bolt\Composer\EventListener;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Event listener for extension Composer operations.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
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

        $rootExtra = $event->getComposer()->getPackage()->getExtra();
        if (realpath($rootExtra['bolt-web-path']) === realpath($rootExtra['bolt-root-path'])) {
            return;
        }

        // Copy package assets to main web path
        $src = 'vendor/' . $package->getName() . '/' . $extra['bolt-assets'];
        $dest = $rootExtra['bolt-web-path'] . '/extensions/vendor/' . $package->getName();

        self::mirror($src, $dest, $event);
    }

    /**
     * Dump the metadata for extension loading on the 'post-autoload-dump' event.
     *
     * @param Event $event
     */
    public static function dump(Event $event)
    {
        $composer = $event->getComposer();

        $extra = $composer->getPackage()->getExtra();
        $includeAssetsDir = realpath($extra['bolt-web-path']) === realpath($extra['bolt-root-path']);

        /** @var PackageDescriptor[] $extensions */
        $extensions = [];

        $finder = self::getInstalledComposerJson();
        foreach ($finder as $jsonFile) {
            $jsonData = json_decode($jsonFile->getContents(), true);
            if (isset($jsonData['type']) && $jsonData['type'] === 'bolt-extension') {
                // Hack to get web path for local extensions on a git install
                $location = strpos($jsonFile->getPath(), 'vendor') === 0 ? 'vendor' : 'local';
                $webPath = sprintf('extensions/%s/%s', $location, $jsonData['name']);

                if ($includeAssetsDir && !empty($jsonData['extra']['bolt-assets'])) {
                    $webPath .= '/' . trim($jsonData['extra']['bolt-assets'], '/');
                }

                $extensions[$jsonData['name']] = PackageDescriptor::parse($composer, $webPath, $jsonFile->getPath(), $jsonData);
            }
        }

        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $fs = new Filesystem();
        $fs->dumpFile($vendorDir . '/autoload.json', json_encode($extensions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Mirror a directory if the two directories don't match.
     *
     * @param string       $source
     * @param string       $dest
     * @param PackageEvent $event
     */
    public static function mirror($source, $dest, PackageEvent $event)
    {
        if (realpath($source) === realpath($dest)) {
            return;
        }
        $fs = new Filesystem();
        try {
            $fs->mirror($source, $dest);
        } catch (IOException $e) {
            $event->getIO()->writeError(sprintf('Mirroring %s to %s failed:', $source, $dest));
            $event->getIO()->writeError($e->getMessage());
        }
    }

    /**
     * Return all the installed extension composer.json files.
     *
     * @return Finder|SplFileInfo[]
     */
    private static function getInstalledComposerJson()
    {
        $finder = new Finder();
        $finder->files()
            ->name('composer.json')
            ->notPath('vendor/composer')
            ->depth(2)
        ;
        try {
            $finder->in(['local']);
        } catch (\InvalidArgumentException $e) {
            // No local extensions are installed
        }
        try {
            $finder->in(['vendor']);
        } catch (\InvalidArgumentException $e) {
            // Composer has not had its autoloader dumped
        }

        return $finder;
    }
}
