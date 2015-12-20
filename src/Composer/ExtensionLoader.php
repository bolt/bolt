<?php

namespace Bolt\Composer;

use Bolt\Extension\ExtensionInterface;
use Bolt\Extension\ResolvedExtension;
use Bolt\Filesystem\Exception\IncludeFileException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\JsonFile;

/**
 * Class to manage loading of extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionLoader
{
    /** @var ResolvedExtension[] */
    protected $extensions = [];
    /** @var string[] */
    protected $map;

    /** @var FilesystemInterface */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
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

        foreach ($autoloadJson->parse() as $package => $loader) {
            if (class_exists($loader['class'])) {
                /** @var ExtensionInterface $class */
                $class = new $loader['class']();
                if ($class instanceof ExtensionInterface) {
                    $phpName = $class->getName();
                    $this->map[$phpName] = $package;
                    $this->extensions[$package] = new ResolvedExtension($class);
                }
            }
        }
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
