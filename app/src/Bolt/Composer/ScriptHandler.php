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
        $root = $event->getComposer();
        $extra = $installedPackage->getExtra();
        $type = $installedPackage->getType();
        print_r($installedPackage);
        print_r($root);
        print_r($event);
        exit;
        if ($type == 'bolt-extension' && isset($extra['bolt-assets'])) {
            $assetdir = $extra['bolt-assets'];
            
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
