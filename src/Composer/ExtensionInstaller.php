<?php
namespace Bolt\Composer;

class ExtensionInstaller
{
    public static function handle($event)
    {
        try {
            $installedPackage = $event->getComposer()->getPackage();
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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
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
