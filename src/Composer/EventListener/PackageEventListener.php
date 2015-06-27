<?php
namespace Bolt\Composer\EventListener;

class PackageEventListener
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
            $destParts = [getcwd(), $pathToPublic, 'extensions', 'vendor', $installedPackage->getName(), $extra['bolt-assets']];
            $dest = realpath(join(DIRECTORY_SEPARATOR, $destParts));
            if ($type === 'bolt-extension' && isset($extra['bolt-assets'])) {
                $sourceParts = [getcwd(), 'vendor', $installedPackage->getName(),$extra['bolt-assets']];
                $source = join(DIRECTORY_SEPARATOR, $sourceParts);
                self::mirror($source, $dest);
            }
        }
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
