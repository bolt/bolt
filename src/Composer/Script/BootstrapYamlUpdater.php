<?php

namespace Bolt\Composer\Script;

use Bolt\Collection\MutableBag;
use Bolt\Common\Str;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Updates .bolt.yml paths for changes with PathResolver introduced in 3.3.
 *
 * NOTE: If debugging this with with xdebug, you will need to run Composer from
 * the vendor/bin/ directory, and set the COMPOSER_ALLOW_XDEBUG=1 environment
 * variable, e.g.
 * <pre>
 * COMPOSER_ALLOW_XDEBUG=1 ./vendor/bin/composer run-script <script name>
 * </pre>
 *
 * @internal
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class BootstrapYamlUpdater
{
    const FILENAME = '.bolt.yml';

    /** @var IOInterface */
    private $io;
    /** @var Filesystem */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param IOInterface $io
     * @param Filesystem  $filesystem
     */
    public function __construct(IOInterface $io, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Create from a Composer event object.
     *
     * @param Event           $event
     * @param Filesystem|null $filesystem
     *
     * @return BootstrapYamlUpdater
     */
    public static function fromEvent(Event $event, Filesystem $filesystem = null)
    {
        return new static($event->getIO(), $filesystem);
    }

    /**
     * Update .bolt.yml file if needed.
     */
    public function update()
    {
        if (!file_exists(static::FILENAME)) {
            return;
        }

        $contents = Yaml::parse(file_get_contents(static::FILENAME)) ?: [];

        if (!isset($contents['paths'])) {
            return;
        }

        $originalPaths = $contents['paths'];
        $contents['paths'] = $newPaths = $this->updatePaths(new MutableBag($originalPaths))->toArray();

        if ($originalPaths === $newPaths) {
            return;
        }

        $this->save($contents);
    }

    /**
     * Save updated data .bolt.yml file, or remove if it matches defaults.
     *
     * @param array $contents
     */
    public function save(array $contents)
    {
        // Custom paths
        if (count($contents['paths']) > 0) {
            $str = Yaml::dump($contents);
            try {
                $this->filesystem->dumpFile(static::FILENAME, $str);
                $version = \Bolt\Version::VERSION;
                $this->io->writeError([
                    "<info>Bolt has updated the <comment>paths</comment> in your <comment>.bolt.yml</comment> file for $version.</info>\n",
                ]);
            } catch (IOException $e) {
                $this->io->writeError([
                    'The <comment>paths</comment> in your <comment>.bolt.yml</comment> file can be simplified.',
                    'You should update your <comment>.bolt.yml</comment> file to this:',
                    '',
                    "<info>$str</info>",
                    '',
                ]);
            }

            return;
        }

        $this->io->writeError('The paths in your <comment>.bolt.yml</comment> file match the defaults now.');

        // No custom paths and no other options = delete.
        if ($onlyPaths = !array_diff_key($contents, ['paths' => 0])) {
            try {
                $this->filesystem->remove(static::FILENAME);
                $this->io->writeError("<info>Since this file is optional we've deleted it for you.</info>\n");
            } catch (IOException $e) {
                $this->io->writeError("<info>It is safe to delete it.</info>\n");
            }

            return;
        }

        // No custom paths, but other options.
        try {
            unset($contents['paths']);
            $this->filesystem->dumpFile(static::FILENAME, Yaml::dump($contents));
            $this->io->writeError("<info>We've removed them from the file for you.</info>\n");
        } catch (IOException $e) {
            $this->io->writeError("<info>It is safe and encouraged to remove them.</info>\n");
        }
    }

    /**
     * Update paths.
     *
     * @param MutableBag $paths
     *
     * @return MutableBag
     */
    public function updatePaths(MutableBag $paths)
    {
        if ($paths->isEmpty()) {
            return $paths;
        }

        if ($web = $paths['web']) {
            if ($web === 'public') {
                $paths->remove('web');
            } elseif (strpos($web, '%site%') !== 0) {
                $paths['web'] = '%site%/' . $web;
            }
        }
        $webLength = strlen($web . '/');

        if ($files = $paths['files']) {
            if ($files === $web . '/files') {
                $paths->remove('files');
            } elseif (strpos($files, $web . '/') === 0) {
                $paths['files'] = '%web%/' . substr($files, $webLength);
            }
        }

        if ($paths->has('themebase')) {
            $themes = $paths->remove('themebase');
            if ($themes === $web . '/theme') {
                // nothing
            } elseif (strpos($themes, $web . '/') === 0) {
                $paths['themes'] = '%web%/' . substr($themes, $webLength);
            }
        }

        if ($paths->has('view')) {
            $boltAssets = $paths->remove('view');
            if ($boltAssets === $web . '/bolt-public/view') {
                // nothing
            } elseif (strpos($boltAssets, $web . '/') === 0) {
                $paths['bolt_assets'] = '%web%/' . substr($boltAssets, $webLength);
            }
        }

        if ($var = $paths['var']) {
            if ($var === 'var') {
                $paths->remove('var');
            } elseif (strpos($var, '%site%') !== 0) {
                $paths['var'] = '%site%/' . $var;
            }
        }
        $var = $var ?: 'var';
        $varLength = strlen($var . '/');

        if ($cache = $paths['cache']) {
            // Handle v4 style layouts
            if (strpos($cache, $var . '/') === 0) {
                $paths['cache'] = '%var%/' . substr($cache, $varLength);
            }
        }

        if ($paths->has('app')) {
            return $paths;
        }

        $appPathKeys = ['cache', 'config', 'database'];
        $appPaths = array_intersect_key($paths->toArray(), array_flip($appPathKeys));
        // Only paths without %app% alias in them.
        $appPaths = array_filter($appPaths, function ($path) {
            return strpos($path, '%app%') === false || strpos($path, 'app') === 0;
        });
        if (empty($appPaths)) {
            return $paths;
        }
        $app = $this->determineMostCommonBaseAppPath($appPaths);

        if ($app === 'app') {
            // "app/" is the default "app" path, so don't bother setting.
            $appPathToTrim = 'app/';
        } elseif ($app === '') {
            // no common base path so just ignore app path alias
            $appPathToTrim = '';
        } else {
            // custom app path, set it.
            $site = $paths->get('site', '.');
            $paths['app'] = '%site%/' . Path::makeRelative($app, $site);
            $appPathToTrim = $app . '/';
        }

        foreach ($appPathKeys as $key) {
            if (!($path = $paths[$key])) {
                continue;
            }
            if ($appPathToTrim && strpos($path, $appPathToTrim) === 0) {
                $path = Str::replaceFirst($path, $appPathToTrim, '');
                if ($path === $key) {
                    $paths->remove($key);
                } else {
                    $paths[$key] = '%app%/' . $path;
                }
            } else {
                $paths[$key] = $path;
            }
        }

        return $paths;
    }

    /**
     * Determine the most common app path.
     *
     * @param string[] $paths
     *
     * @return string
     */
    private function determineMostCommonBaseAppPath($paths)
    {
        $paths = array_values($paths);

        $options = [];
        $options[] = Path::getLongestCommonBasePath($paths);
        if (isset($paths[1])) {
            $options[] = Path::getLongestCommonBasePath([$paths[0], $paths[1]]);
        }
        if (isset($paths[2])) {
            $options[] = Path::getLongestCommonBasePath([$paths[1], $paths[2]]);
            $options[] = Path::getLongestCommonBasePath([$paths[0], $paths[2]]);
        }
        rsort($options);

        return reset($options);
    }
}
