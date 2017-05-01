<?php

namespace Bolt\Composer\Script;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class DirectoryConfigurator
{
    /** @var IOInterface */
    private $io;
    /** @var Filesystem */
    private $filesystem;
    /** @var array */
    private $composerExtra;
    /** @var int */
    private $dirMode;

    /**
     * Constructor.
     *
     * @param IOInterface       $io
     * @param array             $composerExtra
     * @param Filesystem|null   $filesystem
     */
    public function __construct(IOInterface $io, array $composerExtra = [], Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->composerExtra = $composerExtra;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public static function fromEvent(Event $event)
    {
        return new static($event->getIO(), $event->getComposer()->getPackage()->getExtra());
    }

    public function run()
    {
        $this->configureDirs();
    }

    protected function configureDirs()
    {
        $web = $this->configureDir('web', 'public', '', false);
        $themes = $this->configureDir('themes', 'theme', $web . '/');
        $files = $this->configureDir('files', 'files', $web . '/');

        $config = $this->configureDir('config', 'app/config');
        $database = $this->configureDir('database', 'app/database');
        $cache = $this->configureDir('cache', 'app/cache');

        $config = [
            'paths' => [
                'cache'       => $cache,
                'config'      => $config,
                'database'    => $database,
                'web'         => $web,
                'themes'      => $themes,
                'files'       => $files,
                'bolt_assets' => $web . '/bolt-public/view',
            ],
        ];
        $this->filesystem->dumpFile('.bolt.yml', Yaml::dump($config));

        $chmodDirs = [
            'extensions',
            $web . '/extensions',
            $web . '/thumbs',
        ];
        $this->filesystem->chmod($chmodDirs, $this->configureDirMode());
    }

    /**
     * @param string $name
     * @param string $defaultInSkeleton
     * @param string $prefix
     * @param bool   $chmod
     *
     * @return string
     */
    protected function configureDir($name, $defaultInSkeleton, $prefix = '', $chmod = true)
    {
        $default = $this->getOption($name . '-dir', $defaultInSkeleton);

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
        $dir = $this->io->askAndValidate($question, $validator, null, $default);

        $origin = $prefix . $defaultInSkeleton;
        $target = $prefix . $dir;

        $dirMode = $this->configureDirMode();

        if ($origin !== $target) {
            $this->io->writeError(sprintf('Moving <info>%s</info> directory from <info>%s</info> to <info>%s</info>', $name, $origin, $target));
            $this->filesystem->mkdir(dirname($target)); // ensure parent directory exists
            $this->filesystem->rename($origin, $target);
        }

        if ($chmod) {
            $it = (new Finder())->directories()->in($target)->append([$target]);
            $this->filesystem->chmod($it, $dirMode);
        }

        return $target;
    }

    /**
     * Gets the directory mode value, sets umask with it, and returns it.
     *
     * @return int
     */
    protected function configureDirMode()
    {
        if ($this->dirMode === null) {
            $dirMode = $this->getOption('dir-mode', 0777);
            $dirMode = is_string($dirMode) ? octdec($dirMode) : $dirMode;

            umask(0777 - $dirMode);

            $this->dirMode = $dirMode;
        }

        return $this->dirMode;
    }

    /**
     * Get an option from environment variable or composer's extra section.
     *
     * Example: With key "dir-mode" it checks for "BOLT_DIR_MODE" environment variable,
     * then "bolt-dir-mode" in composer's extra section, then returns given default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getOption($key, $default = null)
    {
        if ($value = $this->getEnvOption($key)) {
            return $value;
        }

        $key = strtolower(str_replace('_', '-', $key));

        if (strpos($key, 'bolt-') !== false) {
            $key = 'bolt-' . $key;
        }

        return isset($this->composerExtra[$key]) ? $this->composerExtra[$key] : $default;
    }

    /**
     * @param string $key
     *
     * @return array|false|string
     */
    protected function getEnvOption($key)
    {
        $key = strtoupper(str_replace('-', '_', $key));

        if (strpos($key, 'BOLT_') !== false) {
            $key = 'BOLT_' . $key;
        }

        return getenv($key);
    }
}
