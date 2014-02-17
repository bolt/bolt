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
        $confDir = $options['bolt-conf-dir'];

        if (is_string($dirMode)) {
            $dirMode = octdec($dirMode);
        }

        if (! is_dir($webDir)) {
            echo 'The bolt-web-dir (' . $webDir . ') specified in composer.json was not found in ' . getcwd() . ', exiting.' . PHP_EOL;
            return;
        }

        if (! is_dir($confDir)) {
            echo 'The bolt-conf-dir (' . $confDir . ') specified in composer.json was not found in ' . getcwd() . ', exiting.' . PHP_EOL;
            return;
        }

        $targetDir = $webDir . '/bolt-public/';

        $filesystem = new Filesystem();
        if (! is_dir($targetDir)) {
            $filesystem->mkdir($targetDir, $dirMode);
        }
        foreach (array(
            'css',
            'font',
            'img',
            'js',
            'lib'
        ) as $dir) {
            $filesystem->mirror(__DIR__ . '/../../../view/' . $dir, $targetDir . '/view/' . $dir);
        }
        $filesystem->mirror(__DIR__ . '/../../../classes/upload', $targetDir . '/classes/upload');
        $filesystem->copy(__DIR__ . '/../../../classes/timthumb.php', $targetDir . '/classes/timthumb.php');

        if (! $filesystem->exists($webDir . '/files/')) {
            $filesystem->mirror(__DIR__ . '/../../../../files', $webDir . '/files');
        }

        $filesystem->mirror(__DIR__ . '/../../../../config', $confDir . '/config');
        $filesystem->mirror(__DIR__ . '/../../../../theme', $confDir . '/theme');

        echo "Please tune up index.php, .htaccess, and configs - regarding theme_path as well - in $confDir/config/" . PHP_EOL;
    }

    protected static function getOptions($event)
    {
        $options = array_merge(array(
            'bolt-web-dir' => 'web',
            'bolt-dir-mode' => 0777,
            'bolt-conf-dir' => 'brix/bolt'
        ), $event->getComposer()
            ->getPackage()
            ->getExtra());

        return $options;
    }
}
