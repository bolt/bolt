<?php

namespace Bolt\Composer\Script;

use Bolt\Configuration\PathDependencySorter;
use Bolt\Configuration\PathResolver;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Configures project directories, including:
 * - customizing directories
 * - writing .bolt.yml
 * - moving skeleton directories
 * - updating directory permissions
 *
 * This should only be used for new projects.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DirectoryConfigurator
{
    /** @var IOInterface */
    private $io;
    /** @var Filesystem */
    private $filesystem;
    /** @var PathResolver */
    private $resolver;
    /** @var PathResolver */
    private $defaults;
    /** @var array */
    private $composerExtra;
    /** @var int */
    private $dirMode;

    /**
     * Constructor.
     *
     * @param IOInterface       $io
     * @param array             $composerExtra
     * @param PathResolver|null $resolver
     * @param Filesystem|null   $filesystem
     */
    public function __construct(IOInterface $io, array $composerExtra = [], PathResolver $resolver = null, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->composerExtra = $composerExtra;
        $this->resolver = $resolver ?: new PathResolver(getcwd(), PathResolver::defaultPaths());
        $this->defaults = clone $this->resolver;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Create from a Composer event object.
     *
     * @param Event $event
     *
     * @return DirectoryConfigurator
     */
    public static function fromEvent(Event $event)
    {
        return new static($event->getIO(), $event->getComposer()->getPackage()->getExtra());
    }

    /**
     * Go!
     */
    public function run()
    {
        $this->configureDirs();

        $this->writeYamlConfig();

        $this->moveSkeletonDirs();

        $this->updatePermissions();
    }

    /**
     * Configure dirs from env, composer extra values, and via user input.
     */
    protected function configureDirs()
    {
        // Configure dirs from environment variables and composer extra values.
        foreach ($this->resolver->names() as $name) {
            $default = $this->resolver->raw($name);
            $path = $this->getOption($name . '-dir', $default);
            if ($path !== $default) {
                $this->verbose("Setting <info>$name</> path to <info>$path</>.");
                $this->resolver->define($name, $path);
            }
        }

        if ($this->io->askConfirmation("Do you want to use Bolt's standard folder structure? ")) {
            return;
        }

        PathCustomizer::fromComposer($this->resolver, $this->io)->run();

        $this->io->writeError("\n<info>Customizing!</info>\n");
    }

    /**
     * Writes the .bolt.yml file if paths are not the default.
     */
    protected function writeYamlConfig()
    {
        $customized = [];

        foreach ($this->resolver->names() as $name) {
            $raw = $this->resolver->raw($name);

            if ($raw !== $this->defaults->raw($name)) {
                $customized[$name] = $raw;
            }
        }

        if (!$customized) {
            return;
        }

        $this->io->writeError('Writing customized paths to <comment>.bolt.yml</>');

        $config = [
            'paths' => $customized,
        ];
        $this->filesystem->dumpFile('.bolt.yml', Yaml::dump($config));
    }

    /**
     * Move dirs from skeleton to match given paths.
     */
    protected function moveSkeletonDirs()
    {
        // Sort paths based on their dependencies
        $pathNames = (new PathDependencySorter($this->resolver))->getSortedNames();

        foreach ($pathNames as $name) {
            $this->moveSkeletonDir($name);
        }
    }

    /**
     * Move given dir from skeleton to match PathResolver setting if needed.
     *
     * @param string $name
     */
    protected function moveSkeletonDir($name)
    {
        $root = $this->resolver->resolve('root');
        $target = $this->resolver->resolve($name);
        $target = Path::makeRelative($target, $root);

        $origin = $this->defaults->resolve($name);
        $origin = Path::makeRelative($origin, $root);

        // No need to move if the same.
        if ($origin === $target) {
            return;
        }
        // Don't move the root directory.
        // This could happen if "site" path is moved to a subdir.
        // The subdir will be created later for dependent paths.
        if ($origin === '') {
            return;
        }

        $this->verbose(
            sprintf('Moving <info>%s</> directory from <info>%s</> to <info>%s</>', $name, $origin, $target)
        );

        // ensure parent directory exists
        $this->filesystem->mkdir(dirname($target), $this->getDirMode());

        $this->filesystem->rename($origin, $target);
    }

    /**
     * Update all path resolver directories permissions.
     */
    protected function updatePermissions()
    {
        $pathNames = $this->resolver->names();
        // These should be moved to PathResolver paths eventually.
        $pathNames[] = '%web%/extensions';
        $pathNames[] = '%web%/thumbs';

        $dirMode = $this->getDirMode();
        foreach ($pathNames as $name) {
            $path = $this->resolver->resolve($name);
            $this->filesystem->chmod($path, $dirMode);
        }
    }

    /**
     * @param string|string[] $messages
     */
    protected function verbose($messages)
    {
        $this->io->writeError($messages, true, IOInterface::VERBOSE);
    }

    /**
     * Returns the directory mode.
     *
     * @return int
     */
    protected function getDirMode()
    {
        if ($this->dirMode === null) {
            $dirMode = $this->getOption('dir-mode', 0777);
            $this->dirMode = is_string($dirMode) ? octdec($dirMode) : $dirMode;
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

        if (strpos($key, 'bolt-') !== 0) {
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

        if (strpos($key, 'BOLT_') !== 0) {
            $key = 'BOLT_' . $key;
        }

        return getenv($key);
    }
}
