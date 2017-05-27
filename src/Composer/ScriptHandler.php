<?php

namespace Bolt\Composer;

use Bolt\Composer\Script\BootstrapYamlUpdater;
use Bolt\Composer\Script\DirectoryConfigurator;
use Bolt\Composer\Script\Options;
use Bolt\Composer\Script\ScriptHandlerUpdater;
use Bolt\Exception\BootException;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler
{
    /** @var \Silex\Application */
    private static $app;
    /** @var int */
    private static $dirMode;

    /**
     * Install Bolt's assets.
     *
     * This should be ran on "post-install-cmd" and "post-update-cmd" events.
     *
     * @param Event $event
     * @param bool  $checkForCreateProject
     */
    public static function installAssets(Event $event, $checkForCreateProject = true)
    {
        /*
         * Ugly hack to prevent application from being booted before configureProject can be called.
         */
        global $argv;
        if ($checkForCreateProject && strpos(implode(' ', $argv), 'create-project') > 0) {
            return;
        }

        static::runUpdateProjectFromAssets($event);

        $webDir = static::getWebDir($event);
        if ($webDir === null) {
            return;
        }

        $assetDir = static::getDir($event, 'bolt_assets', $webDir . '/bolt-public/view');

        $filesystem = new Filesystem();

        $originDir = __DIR__ . '/../../app/view/';
        $targetDir = rtrim($assetDir, '/') . '/';

        $event->getIO()->writeError(sprintf('Installing assets to <info>%s</info>', rtrim($targetDir, '/')));
        foreach (['css', 'fonts', 'img', 'js'] as $dir) {
            $filesystem->mirror($originDir . $dir, $targetDir . $dir, null, ['override' => true, 'delete' => true]);
        }
    }

    /**
     * Install Bolt's default themes and files.
     *
     * This should be ran on "post-create-project-cmd" event.
     *
     * @param Event $event
     */
    public static function installThemesAndFiles(Event $event)
    {
        static::configureDirMode($event);

        $webDir = static::getWebDir($event);
        if ($webDir === null) {
            return;
        }

        $filesystem = new Filesystem();

        $root = __DIR__ . '/../../';

        $target = static::getDir($event, 'files');
        $event->getIO()->writeError(sprintf('Installing <info>files</info> to <info>%s</info>', $target));
        $filesystem->mirror($root . 'files', $target, null, ['override' => true]);

        $target = static::getDir($event, 'themebase');
        $event->getIO()->writeError(sprintf('Installing <info>themes</info> to <info>%s</info>', $target));
        $filesystem->mirror($root . 'theme', $target, null, ['override' => true]);
    }

    /**
     * Updates project existing structure if needed.
     *
     * @param Event $event
     */
    public static function updateProject(Event $event)
    {
        (new BootstrapYamlUpdater($event->getIO()))->update();
    }

    /**
     * Configures installation's directory structure.
     *
     * The configured paths are written to .bolt.yml
     * and the skeleton structure is modified accordingly.
     *
     * @param Event $event
     */
    public static function configureProject(Event $event)
    {
        DirectoryConfigurator::fromEvent($event)->run();

        // reset app so the path changes are picked up
        static::$app = null;

        // Install assets here since they they were skipped above
        static::installAssets($event, false);
    }

    /**
     * Gets the directory mode value, sets umask with it, and returns it.
     *
     * @param Event $event
     *
     * @return number
     */
    protected static function configureDirMode(Event $event)
    {
        if (static::$dirMode === null) {
            $options = Options::fromEvent($event);
            $dirMode = $options->getDirMode();
            $dirMode = is_string($dirMode) ? octdec($dirMode) : $dirMode;

            umask(0777 - $dirMode);

            static::$dirMode = $dirMode;
        }

        return static::$dirMode;
    }

    /**
     * Checks if updateProject is in composer.json. If not,
     * this adds it / shows how to add it, and then runs updateProject.
     *
     * @param Event $event
     */
    protected static function runUpdateProjectFromAssets(Event $event)
    {
        if ($event->getName() !== ScriptEvents::POST_UPDATE_CMD) {
            return;
        }

        $updater = new ScriptHandlerUpdater($event);

        if (!$updater->needsUpdate()) {
            return;
        }
        $updater->update();

        static::updateProject($event);
    }

    /**
     * Gets the web directory either from configured application or composer's extra section/environment variable.
     *
     * If the web directory doesn't exist an error is emitted and null is returned.
     *
     * @param Event $event
     *
     * @return string|null
     */
    protected static function getWebDir(Event $event)
    {
        $webDir = static::getDir($event, 'web', 'public');

        if (!is_dir($webDir)) {
            $error = '<error>The web directory (%s) was not found in %s, can not install assets.</error>';
            $event->getIO()->write(sprintf($error, $webDir, getcwd()));

            return null;
        }

        return $webDir;
    }

    /**
     * Gets the directory requested either from configured application or composer's extra section/environment variable.
     *
     * @param Event       $event
     * @param string      $name
     * @param string|null $default
     *
     * @return string
     */
    protected static function getDir(Event $event, $name, $default = null)
    {
        try {
            $app = static::getApp($event);

            $dir = $app['path_resolver']->resolve($name);
            $dir = Path::makeRelative($dir, getcwd());
        } catch (BootException $e) {
            $dir = static::getOption($event, $name . '-dir', $default);
        }

        return rtrim($dir, '/');
    }

    /**
     * Loads the application once from bootstrap file (which is configured with .bolt.yml/.bolt.php file).
     *
     * NOTE: This only works on the "post-autoload-dump" command as the autoload.php file has not been generated before
     * that point.
     *
     * @param Event $event
     *
     * @return \Silex\Application
     */
    protected static function getApp(Event $event)
    {
        if (static::$app === null) {
            $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
            static::$app = require $vendorDir . '/bolt/bolt/app/bootstrap.php';
        }

        return static::$app;
    }

    /**
     * Get an option from environment variable or composer's extra section.
     *
     * Example: With key "dir-mode" it checks for "BOLT_DIR_MODE" environment variable,
     * then "bolt-dir-mode" in composer's extra section, then returns given default value.
     *
     * @param Event  $event
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected static function getOption(Event $event, $key, $default = null)
    {
        $options = Options::fromEvent($event);

        return $options->get($key, $default);
    }
}
