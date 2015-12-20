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

        foreach ($autoloadJson->parse() as $loader) {
            if (class_exists($loader['class'])) {
                /** @var ExtensionInterface $class */
                $class = new $loader['class']();
                if ($class instanceof ExtensionInterface) {
                    $name = $class->getName();
                    $this->extensions[$name] = new ResolvedExtension($class);
                }
            }
        }
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
        if (isset($this->extensions[$name])) {
            return $this->extensions[$name]->getInnerExtension();
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
        if (isset($this->extensions[$name])) {
            return $this->extensions[$name];
        }
    }
}
