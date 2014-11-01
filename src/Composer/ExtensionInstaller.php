<?php
namespace Bolt\Composer;

class ExtensionInstaller
{
    public static function handle($event)
    {
        $installedPackage = $event->getComposer()->getPackage();
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
                $this->mirror($fromPath, $path);
            }
        }
    }
    
    public function mirror($source, $dest) 
    {
        mkdir($dest, 0755);
        $iterator = new RecursiveIteratorIterator( 
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
          if ($item->isDir()) {
            mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          }
        }
    }
}

