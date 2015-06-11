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

        foreach (['css', 'fonts', 'img', 'js'] as $dir) {
            $filesystem->mirror(__DIR__ . '/../../app/view/' . $dir, $targetDir . '/view/' . $dir);
        }

        if (!$filesystem->exists($webDir . '/files/')) {
            $filesystem->mirror(__DIR__ . '/../../files', $webDir . '/files');
        }

        if (!$filesystem->exists($webDir . '/theme/')) {
            $filesystem->mkdir($webDir . '/theme/', $dirMode);
            $filesystem->mirror(__DIR__ . '/../../theme', $webDir . '/theme');
        }

        $event->getIO()->write('<info>Installed assets</info>');
    }

    /**
     * Installing bootstrap file
     *
     * @param Event $event
     */
    public static function installApp(Event $event)
    {
        $options = self::getOptions($event);

        $dirMode = $options['bolt-dir-mode'];
        $appDir = $options['bolt-app-dir'];

        if (is_string($dirMode)) {
            $dirMode = octdec($dirMode);
        }

        umask(0777 - $dirMode);

        $fs = new Filesystem();

        $app_dirs = ['database', 'cache', 'config'];

        // Now we handle the app directory creation
        if (!$fs->exists($appDir)) {

            // Create app directories and copy their content
            $fs->mkdir($appDir, $dirMode);
            foreach ($app_dirs as $dir) {
                $fs->mirror(__DIR__ . '/../../app/' . $dir, $appDir . '/' . $dir);
            }

            // Copy command line utility
            foreach (['bootstrap.php', 'nut'] as $app_file) {
                $fs->copy(__DIR__ . '/../../app/'. $app_file, $appDir . '/' . $app_file);
            }

            // Rename dist files
            foreach (['config', 'contenttypes', 'menu', 'permissions', 'routing', 'taxonomy'] as $config_file) {
                $file = $appDir . '/config/' . $config_file . '.yml.dist';
                $fs->rename($file, str_replace('.dist', '', $file));
            }

            $event->getIO()->write('<info>Installed app</info>');
        } else {
            $event->getIO()->write('<info>app directory already exists in ' . getcwd() . '</info>');
        }

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

        $generator = new BootstrapGenerator(true, $webDir);
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
            [
                'bolt-web-dir'  => 'web',
                'bolt-app-dir'  => 'app',
                'bolt-dir-mode' => 0777
            ],
            $event->getComposer()->getPackage()->getExtra()
        );

        return $options;
    }
}
