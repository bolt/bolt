<?php

namespace Bolt\Composer\EventListener;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;
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
        $packageAssets = 'vendor/' . $package->getName() . '/' . $extra['bolt-assets'];

        // Copy package assets to main web path
        $rootExtra = $event->getComposer()->getPackage()->getExtra();
        $dest = realpath($rootExtra['bolt-web-path'] . '/extensions/' . $packageAssets);

        self::mirror($packageAssets, $dest);
    }

    /**
     * Dump the metadata for extension loading on the 'post-autoload-dump' event.
     *
     * @param Event $event
     */
    public static function dump(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $finder = self::getInstalledComposerJson();
        $extensions = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $extensions = self::parseComposerJson($file, $extensions);
        }

        $fs = new Filesystem();
        $fs->dumpFile($vendorDir . '/autoload.json', json_encode($extensions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
            /** @var $item SplFileInfo */
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

    /**
     * Return all the installed extension composer.json files.
     *
     * @return Finder
     */
    private static function getInstalledComposerJson()
    {
        $finder = new Finder();
        $finder->files()
            ->name('composer.json')
            ->notPath('vendor/composer')
            ->contains('"bolt-class"')
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

    /**
     * Parse a composer.json file and return specific metadata.
     *
     * @param SplFileInfo $jsonFile
     * @param array       $extensions
     *
     * @return array
     */
    private static function parseComposerJson(SplFileInfo $jsonFile, array $extensions)
    {
        $jsonData = json_decode($jsonFile->getContents(), true);
        $key = $jsonData['name'];
        $extensions[$key] = [
            'name'  => $jsonData['name'],
            'class' => $jsonData['extra']['bolt-class'],
            'path'  => $jsonFile->getPath(),
        ];

        return $extensions;
    }
}
