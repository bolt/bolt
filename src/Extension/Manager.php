<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Exception\IncludeFileException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Logger\FlashLoggerInterface;

/**
 * Class to manage loading of extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
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

    /**
     * Constructor.
     *
     * @param FilesystemInterface  $filesystem
     * @param FlashLoggerInterface $flashLogger
     */
    public function __construct(FilesystemInterface $filesystem, FlashLoggerInterface $flashLogger)
    {
        $this->filesystem = $filesystem;
        $this->flashLogger = $flashLogger;
    }

    /**
     * Load a collection of extension classes.
     */
    public function load()
    {
        /** @var JsonFile $autoloadJson */
        $autoloadJson = $this->filesystem->get('vendor/autoload.json');
        if (!$autoloadJson->exists()) {
            return;
        }

        try {
            $this->filesystem->includeFile('vendor/autoload.php');
        } catch (IncludeFileException $e) {
            return;
        }

        $this->autoload = (array) $autoloadJson->parse();
        foreach ($this->autoload as $loader) {
            $composerName = $loader['name'];
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
     * @param $name
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
     * @param $name
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
     * @param $name
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
}
