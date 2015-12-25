<?php

namespace Bolt\Extension;

use Bolt\Config;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IncludeFileException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Legacy\ExtensionsTrait;
use Bolt\Logger\FlashLoggerInterface;
use Cocur\Slugify\Slugify;

/**
 * Class to manage loading of extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    use ExtensionsTrait;

    /** @var ResolvedExtension[] */
    protected $extensions = [];
    /** @var string[] */
    protected $map = [];
    /** @var array */
    protected $autoload;

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var FlashLoggerInterface */
    private $flashLogger;
    /** @var Config */
    private $config;

    /**
     * Constructor.
     *
     * @param FilesystemInterface  $filesystem
     * @param FlashLoggerInterface $flashLogger
     */
    public function __construct(FilesystemInterface $filesystem, FlashLoggerInterface $flashLogger, Config $config)
    {
        $this->filesystem = $filesystem;
        $this->flashLogger = $flashLogger;
        $this->config = $config;
    }

    /**
     * Load a collection of extension classes.
     */
    public function load()
    {
        try {
            /** @var JsonFile $autoloadJson */
            $autoloadJson = $this->filesystem->get('vendor/autoload.json');
            $this->filesystem->includeFile('vendor/autoload.php');
        } catch (FileNotFoundException $e) {
            return;
        } catch (IncludeFileException $e) {
            return;
        }

        $this->autoload = (array) $autoloadJson->parse();
        foreach ($this->autoload as $loader) {
            $composerName = $loader['name'];
            if ($loader['valid'] === false) {
                // Skip loading if marked invalid
                continue;
            }
            $extConfig = $this->config->get('extensions', []);
            if (isset($extConfig[$composerName]) && $extConfig[$composerName] === false) {
                // Skip loading if marked disabled
                continue;
            }

            if (class_exists($loader['class'])) {
                /** @var ExtensionInterface $class */
                $class = new $loader['class']();
                if ($class instanceof ExtensionInterface) {
                    $phpName = $class->getName();
                    $this->map[$phpName] = $composerName;
                    $this->extensions[$composerName] = new ResolvedExtension($class);
                } else {
                    $this->flashLogger->error(sprintf('Extension package %s base class %s does not implement \\Bolt\\Extension\\ExtensionInterface and has been skipped.', $loader['name'], $loader['class']));
                }
            } else {
                $this->flashLogger->error(sprintf("Extension package %s has an invalid class '%s' and has been skipped.", $loader['name'], $loader['class']));
            }
        }
    }

    /**
     * Insert an extension.
     *
     * This should only be used during bootstrap… You probably don't want to use this function.
     *
     * @param ExtensionInterface $extension
     */
    public function add(ExtensionInterface $extension)
    {
        $name = Slugify::create()->slugify($extension->getVendor() . '/' . $extension->getName(), '/');
        $this->extensions[$name] = $extension;
    }

    /**
     * Return the generated autoloading cache.
     *
     * @return array
     */
    public function getAutoload()
    {
        return $this->autoload;
    }

    /**
     * Get all installed extensions.
     *
     * @return ResolvedExtension[]
     */
    public function all()
    {
        return $this->extensions;
    }

    /**
     * Get an installed extension class.
     *
     * @param string $name
     *
     * @return ExtensionInterface|null
     */
    public function get($name)
    {
        if ($key = $this->getMappedKey($name)) {
            return $this->extensions[$key]->getInnerExtension();
        }
    }

    /**
     * Get the resolved form of an installed extension class.
     *
     * @param string $name
     *
     * @return ResolvedExtension|null
     */
    public function getResolved($name)
    {
        if ($key = $this->getMappedKey($name)) {
            return $this->extensions[$key];
        }
    }

    /**
     * Return the in-use extension name map.
     *
     * @return string[]
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * Resolve a Composer or PHP extension name to the stored key.
     *
     * @param string $name
     *
     * @return string|null
     */
    private function getMappedKey($name)
    {
        $key = null;
        if (isset($this->map[$name])) {
            $key = $this->map[$name];
        } elseif (isset($this->extensions[$name])) {
            $key = $name;
        }

        return $key;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @internal Do not use! For legacy support only.
     */
    protected function getApp()
    {
        // Yes Carson, this is only here to annoy you!
        // Merry Christmas my good friend, and here's to another wonderful year of working together
        return \Bolt\Configuration\ResourceManager::getApp();
    }
}
