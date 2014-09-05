<?php
/**
 * Based on Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
 * @see https://github.com/sensio/SensioDistributionBundle/blob/master/Composer/ScriptHandler.php
 */

namespace Bolt\Composer;

use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    public static function installAssets($event)
    {
        $options = self::getOptions($event);
        $webDir = $options['bolt-web-dir'];
        $dirMode = $options['bolt-dir-mode'];
        if (is_string($dirMode)) {
            $dirMode = octdec($dirMode);
        }

        if (!is_dir($webDir)) {
            echo 'The bolt-web-dir (' . $webDir . ') specified in composer.json was not found in ' . getcwd() . ', can not install assets.' . PHP_EOL;

            return;
        }

        $targetDir = $webDir . '/bolt-public/';

        $filesystem = new Filesystem();
        $filesystem->remove($targetDir);
        $filesystem->mkdir($targetDir, $dirMode);
        //$filesystem->mkdir($targetDir, $dirMode);
        foreach (array('css', 'fonts', 'img', 'js', 'lib') as $dir) {
            $filesystem->mirror(__DIR__ . '/../../../view/' . $dir, $targetDir . '/view/' . $dir);
        }

        if (!$filesystem->exists($webDir . '/files/')) {
            $filesystem->mirror(__DIR__ . '/../../../../files', $webDir . '/files');
        }
    }
     
    
    public static function extensions($event) {
        $installedPackage = $event->getOperation()->getPackage();
        $rootExtra = $event->getComposer()->getPackage()->getExtra();
        $extra = $installedPackage->getExtra();
        if(isset($extra['bolt-assets'])) {
            $type = $installedPackage->getType();
            $pathToPublic = $rootExtra['bolt-web-path'];
            
            // Get the path from extensions base through to public
            $parts = array(getcwd(),$pathToPublic,"extensions",'vendor',$installedPackage->getName(), $extra['bolt-assets']);
            $path = join(DIRECTORY_SEPARATOR, $parts);
            if ($type == 'bolt-extension' && isset($extra['bolt-assets'])) {
                $fromParts = array(getcwd(), 'vendor', $installedPackage->getName(),$extra['bolt-assets']);
                $fromPath = join(DIRECTORY_SEPARATOR, $fromParts);
                $filesystem = new Filesystem();
                $filesystem->mirror($fromPath,$path);
            }
        }
    }

    protected static function getOptions($event)
    {
        $options = array_merge(
            array(
                'bolt-web-dir' => 'web',
                'bolt-dir-mode' => 0777
            ),
            $event->getComposer()->getPackage()->getExtra()
        );

        return $options;
    }
}
