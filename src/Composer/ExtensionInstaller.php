<?php
namespace Bolt\Composer;

use Composer\EventDispatcher\Event;
use Composer\Installer\PackageEvent;
use Composer\Script\Event as ScriptEvent;

class ExtensionInstaller
{
    /**
     * Event handler for composer package events
     *
     * @param \Composer\EventDispatcher\Event $event
     */
    public static function handle($event)
    {
        try {
            $operation = $event->getOperation();
            if (method_exists($operation, 'getPackage')) {
                $installedPackage = $operation->getPackage();
            } elseif (method_exists($operation, 'getTargetPackage')) {
                $installedPackage = $operation->getTargetPackage();
            } else {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        $rootExtra = $event->getComposer()->getPackage()->getExtra();
        $extra = $installedPackage->getExtra();
        if (isset($extra['bolt-assets'])) {
            $type = $installedPackage->getType();
            $pathToPublic = $rootExtra['bolt-web-path'];

            // Get the path from extensions base through to public
            $parts = array(getcwd(),$pathToPublic,"extensions",'vendor',$installedPackage->getName(), $extra['bolt-assets']);
            $path = join(DIRECTORY_SEPARATOR, $parts);
            if ($type == 'bolt-extension' && isset($extra['bolt-assets'])) {
                $fromParts = array(getcwd(), 'vendor', $installedPackage->getName(),$extra['bolt-assets']);
                $fromPath = join(DIRECTORY_SEPARATOR, $fromParts);
                self::mirror($fromPath, $path);
            }
        }
    }

    public static function mirror($source, $dest)
    {
        @mkdir($dest, 0755, true);

        // We only want to do this if the two directories don't match
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
                $new = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!is_dir($new)) {
                    mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }
}
