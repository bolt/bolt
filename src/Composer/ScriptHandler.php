<?php
/**
 * Based on Sensio\Bundle\DistributionBundle\Composer\ScriptHandler.
 *
 * @see https://github.com/sensio/SensioDistributionBundle/blob/master/Composer/ScriptHandler.php
 */

namespace Bolt\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    /**
     * Install basic assets and create needed directories.
     *
     * @param Event $event
     */
    public static function installAssets(Event $event)
    {
        $options = self::getOptions($event);
        $webDir = $options['bolt-web-dir'];
        $dirMode = $options['bolt-dir-mode'];
        if (is_string($dirMode)) {
            $dirMode = octdec($dirMode);
        }

        umask(0777 - $dirMode);

        if (!is_dir($webDir)) {
            echo 'The bolt-web-dir (' . $webDir . ') specified in composer.json was not found in ' . getcwd() . ', can not install assets.' . PHP_EOL;

            return;
        }

        $targetDir = $webDir . '/bolt-public/';

        $filesystem = new Filesystem();
        $filesystem->remove($targetDir);
        $filesystem->mkdir($targetDir, $dirMode);

        foreach (array('css', 'fonts', 'img', 'js') as $dir) {
            $filesystem->mirror(__DIR__ . '/../../app/view/' . $dir, $targetDir . '/view/' . $dir);
        }

        if (!$filesystem->exists($webDir . '/files/')) {
            $filesystem->mirror(__DIR__ . '/../../files', $webDir . '/files');
        }

        if (!$filesystem->exists($webDir . '/theme/')) {
            $filesystem->mkdir($webDir . '/theme/', $dirMode);
            $filesystem->mirror(__DIR__ . '/../../theme', $webDir . '/theme');
        }

        // The first check handles the case where the bolt-web-dir is different to the root.
        // If the first works, then the second won't need to run
        if (!$filesystem->exists(getcwd() . '/extensions/')) {
            $filesystem->mkdir(getcwd() . '/extensions/', $dirMode);
        }

        if (!$filesystem->exists($webDir . '/extensions/')) {
            $filesystem->mkdir($webDir . '/extensions/', $dirMode);
        }

        // Now we handle the app directory creation
        $appDir = $options['bolt-app-dir'];
        if (!$filesystem->exists($appDir)) {
            $filesystem->mkdir($appDir, $dirMode);
            $filesystem->mkdir($appDir . '/database/', $dirMode);
            $filesystem->mkdir($appDir . '/cache/',    $dirMode);
            $filesystem->mkdir($appDir . '/config/',   $dirMode);
        }

        $event->getIO()->write('<info>Installed assets</info>');
    }

    /**
     * Installing bootstrap file
     *
     * @param Event $event
     */
    public static function installBootstrap(Event $event)
    {
        $options = self::getOptions($event);
        $webDir = $options['bolt-web-dir'];

        if (!is_dir($webDir)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($webDir, $options['bolt-dir-mode']);
        }

        $generator = new BootstrapGenerator($webDir, $webDir);
        $generator->create();

        $event->getIO()->write('<info>Installed bootstrap</info>');
    }

    /**
     * Get a default set of options.
     *
     * @param Event $event
     *
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        $options = array_merge(
            array(
                'bolt-web-dir'  => 'web',
                'bolt-app-dir'  => 'app',
                'bolt-dir-mode' => 0777
            ),
            $event->getComposer()->getPackage()->getExtra()
        );

        return $options;
    }
}
