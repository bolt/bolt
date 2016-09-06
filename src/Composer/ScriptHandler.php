<?php

namespace Bolt\Composer;

use Bolt\Exception\LowlevelException;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
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

        $webDir = static::getWebDir($event);
        if ($webDir === null) {
            return;
        }

        $filesystem = new Filesystem();

        $originDir = __DIR__ . '/../../app/view/';
        $targetDir = $webDir . '/bolt-public/view/';

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
     * Configures installation's directory structure.
     *
     * The configured paths are written to .bolt.yml
     * and the skeleton structure is modified accordingly.
     *
     * @param Event $event
     */
    public static function configureProject(Event $event)
    {
        $web = static::configureDir($event, 'web', 'public', '', false);
        $themes = static::configureDir($event, 'theme', 'theme', $web . '/');
        $files = static::configureDir($event, 'files', 'files', $web . '/');

        $config = static::configureDir($event, 'config', 'app/config');
        $database = static::configureDir($event, 'database', 'app/database');
        $cache = static::configureDir($event, 'cache', 'app/cache');

        static::configureGitIgnore($event);

        $config = [
            'paths' => [
                'cache'     => $cache,
                'config'    => $config,
                'database'  => $database,
                'web'       => $web,
                'themebase' => $themes,
                'files'     => $files,
                'view'      => $web . '/bolt-public/view',
            ],
        ];

        $filesystem = new Filesystem();

        $filesystem->dumpFile('.bolt.yml', Yaml::dump($config));

        $chmodDirs = [
            'extensions',
            $web . '/extensions',
            $web . '/thumbs',
        ];
        $filesystem->chmod($chmodDirs, static::configureDirMode($event));

        // reset app so the path changes are picked up
        static::$app = null;

        // Install assets here since they they were skipped above
        static::installAssets($event, false);
    }

    protected static function configureDir(Event $event, $name, $defaultInSkeleton, $prefix = '', $chmod = true)
    {
        $default = static::getOption($event, $name . '-dir', $defaultInSkeleton);

        $validator = function ($value) use ($prefix, $name) {
            if ($prefix) {
                $basePath = Path::makeAbsolute($prefix, getcwd());
                $path = Path::makeAbsolute($value, $basePath);
                if (!Path::isBasePath($basePath, $path)) {
                    throw new \RuntimeException("The $name directory must be inside the $prefix directory.");
                }
            }

            return Path::canonicalize($value);
        };

        $default = $validator($default);

        $relative = $prefix ? '<comment>' . $prefix . '</comment>' : 'project root';
        $question = sprintf('<info>Where do you want your <comment>%s</comment> directory? (relative to %s) [default: <comment>%s</comment>] </info>', $name, $relative, $default);
        $dir = $event->getIO()->askAndValidate($question, $validator, null, $default);

        $fs = new Filesystem();

        $origin = $prefix . $defaultInSkeleton;
        $target = $prefix . $dir;

        $dirMode = static::configureDirMode($event);

        if ($dir !== $defaultInSkeleton) {
            $event->getIO()->writeError(sprintf('Moving <info>%s</info> directory from <info>%s</info> to <info>%s</info>', $name, $origin, $target));
            $fs->mkdir(dirname($target)); // ensure parent directory exists
            $fs->rename($origin, $target);
        }

        if ($chmod) {
            $it = (new Finder())->directories()->in($target)->append([$target]);
            $fs->chmod($it, $dirMode);
        }

        return $target;
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
            $dirMode = static::getOption($event, 'dir-mode', 0777);
            $dirMode = is_string($dirMode) ? octdec($dirMode) : $dirMode;

            umask(0777 - $dirMode);

            static::$dirMode = $dirMode;
        }

        return static::$dirMode;
    }

    /**
     * Optionally copy in Bolt's .gitignore file.
     *
     * @param Event $event
     */
    protected static function configureGitIgnore(Event $event)
    {
        $boltDir = sprintf('%s/bolt/bolt/', $event->getComposer()->getConfig()->get('vendor-dir'));
        $question = sprintf(
            '<info>Do you want to import the <comment>.gitignore</comment> file from <comment>%s</comment>] </info>',
            $boltDir
        );
        $confirm = $event->getIO()->askConfirmation($question, true);
        if ($confirm) {
            $fs = new Filesystem();
            $fs->copy($boltDir . '.gitignore', getcwd() . '/.gitignore', true);
        }
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

            $dir = $app['resources']->getPath($name);
            $dir = Path::makeRelative($dir, getcwd());
        } catch (LowlevelException $e) {
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
        $key = 'bolt-' . $key;

        if ($value = getenv(strtoupper(str_replace('-', '_', $key)))) {
            return $value;
        }

        $extra = $event->getComposer()->getPackage()->getExtra();

        return isset($extra[$key]) ? $extra[$key] : $default;
    }
}
