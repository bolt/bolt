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
     * @param Event      $event
     * @param array|bool $options
     */
    public static function installAssets(Event $event, $options = false)
    {
        $filesystem = new Filesystem();

        if (false === $options) {
            $options = self::getOptions($event);
        }
        $webDir = $options['bolt-web-dir'];
        $dirMode = is_string($options['bolt-dir-mode']) ? octdec($options['bolt-dir-mode']) : $options['bolt-dir-mode'];
        umask(0777 - $dirMode);

        // Set up target directory
        $targetDir = $webDir . '/bolt-public/';
        $filesystem->remove($targetDir);
        $filesystem->mkdir($targetDir, $dirMode);

        if (!is_dir($webDir)) {
            $event->getIO()->write(sprintf('<error>The bolt-web-dir (%s) specified in composer.json was not found in %s, can not install assets.</error>', $webDir, getcwd()));

            return;
        }

        foreach (['css', 'fonts', 'img', 'js'] as $dir) {
            $filesystem->mirror(__DIR__ . '/../../app/view/' . $dir, $targetDir . '/view/' . $dir, null, ['override' => true]);
        }

        if (!$filesystem->exists($webDir . '/files/')) {
            $filesystem->mirror(__DIR__ . '/../../files', $webDir . '/files', null, ['override' => true]);
        }

        if (!$filesystem->exists($webDir . '/theme/')) {
            $filesystem->mkdir($webDir . '/theme/', $dirMode);
            $filesystem->mirror(__DIR__ . '/../../theme', $webDir . '/theme', null, ['override' => true]);
        }

        // The first check handles the case where the bolt-web-dir is different to the root.
        // If thie first works, then the second won't need to run
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
    }

    /**
     * Bootstrap a new Composer based install.
     *
     * @param Event $event
     */
    public static function bootstrap(Event $event)
    {
        $defaultOptions = self::getOptions($event);
        $webRoot = $event->getIO()->askConfirmation('<info>Do you want your web directory to be a separate folder to root? [y/n] </info>', $defaultOptions['bolt-separate-web-dir']);

        if ($webRoot) {
            $defaultDir = $defaultOptions['bolt-web-dir'];
            $webDirName  = $event->getIO()->ask('<info>What do you want your public directory to be named? [default: ' . $defaultDir . '] </info>', $defaultDir);
            $webDirName  = trim($webDirName, '/');
            $assetDir = './' . $webDirName;
        } else {
            $webDirName  = null;
            $assetDir = '.';
        }

        $generator = new BootstrapGenerator($webRoot, $webDirName);
        $generator->create();
        $options = array_merge($defaultOptions, ['bolt-web-dir' => $assetDir]);
        self::installAssets($event, $options);
        $event->getIO()->write('<info>Your project has been setup</info>');
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
                'bolt-separate-web-dir' => true,
                'bolt-web-dir'          => 'public',
                'bolt-app-dir'          => 'app',
                'bolt-dir-mode'         => 0777,
            ],
            $event->getComposer()->getPackage()->getExtra()
        );

        return $options;
    }
}
