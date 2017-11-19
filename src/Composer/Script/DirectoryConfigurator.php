<?php

namespace Bolt\Composer\Script;

use Bolt\Configuration\PathDependencySorter;
use Bolt\Configuration\PathResolver;
use Bolt\Nut\Output\NutStyleInterface;
use Bolt\Nut\Style\NutStyle;
use Composer\Script\Event;
use Symfony\Component\Console\Output\OutputInterface;
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
 * @internal
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class DirectoryConfigurator
{
    /** @var NutStyleInterface */
    private $io;
    /** @var Filesystem */
    private $filesystem;
    /** @var PathResolver */
    private $resolver;
    /** @var PathResolver */
    private $defaults;
    /** @var Options */
    private $options;

    /**
     * Constructor.
     *
     * @param NutStyleInterface $io
     * @param Options|null      $options
     * @param PathResolver|null $resolver
     * @param Filesystem|null   $filesystem
     */
    public function __construct(
        NutStyleInterface $io,
        Options $options = null,
        PathResolver $resolver = null,
        Filesystem $filesystem = null
    ) {
        $this->io = $io;
        $this->options = $options ?: new Options();
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
        $io = NutStyle::fromComposer($event->getIO());
        $options = Options::fromEvent($event);

        return new static($io, $options);
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
    private function configureDirs()
    {
        // Configure dirs from environment variables and composer extra values.
        foreach ($this->resolver->names() as $name) {
            $default = $this->resolver->raw($name);
            $path = $this->options->get($name . '-dir', $default);
            if ($path !== $default) {
                $this->verbose("Setting <info>$name</> path to <info>$path</>.");
                $this->resolver->define($name, $path);
            }
        }

        if ($this->io->confirm("Do you want to use Bolt's standard folder structure?")) {
            return;
        }

        (new PathCustomizer($this->resolver, $this->io))->run();

        $this->io->writeln("\n<info>Customizing!</info>\n");
    }

    /**
     * Writes the .bolt.yml file if paths are not the default.
     */
    private function writeYamlConfig()
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

        $this->io->writeln('Writing customized paths to <comment>.bolt.yml</comment>');

        $config = [
            'paths' => $customized,
        ];
        $this->filesystem->dumpFile('.bolt.yml', Yaml::dump($config));
    }

    /**
     * Move dirs from skeleton to match given paths.
     */
    private function moveSkeletonDirs()
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
    private function moveSkeletonDir($name)
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
            sprintf('Moving <info>%s</info> directory from <info>%s</info> to <info>%s</info>', $name, $origin, $target)
        );

        // ensure parent directory exists
        $this->filesystem->mkdir(dirname($target), $this->options->getDirMode());
        if ($this->filesystem->exists($origin)) {
            $this->filesystem->rename($origin, $target);
        } else {
            $this->filesystem->mkdir($target, $this->options->getDirMode());
        }

        // Update defaults with the change, so when moving sub-directories the origin has the updated parent path.
        $this->defaults->define($name, $this->resolver->raw($name));
    }

    /**
     * Update all path resolver directories permissions.
     */
    private function updatePermissions()
    {
        $pathNames = $this->resolver->names();
        // These should be moved to PathResolver paths eventually.
        $pathNames[] = '%web%/extensions';
        $pathNames[] = '%web%/thumbs';

        $dirMode = $this->options->getDirMode();
        foreach ($pathNames as $name) {
            $path = $this->resolver->resolve($name);
            if (!$this->filesystem->exists($path)) {
                $this->filesystem->mkdir($path);
            }
            $this->filesystem->chmod($path, $dirMode);
        }
    }

    /**
     * @param string|string[] $messages
     */
    private function verbose($messages)
    {
        $this->io->writeln($messages, OutputInterface::VERBOSITY_VERBOSE);
    }
}
