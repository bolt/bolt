<?php

namespace Bolt\Composer;

use Bolt\Extension\ExtensionInterface;
use Bolt\Extension\ResolvedExtension;
use Bolt\Filesystem\Exception\IncludeFileException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\JsonFile;

/**
 * Class to manage autoloading functionality for extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionFinder
{
    /** @var array */
    protected $extensions;

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
     *
     * @return \Bolt\Extension\ResolvedExtension[]
     */
    public function load()
    {
        $classes = [];

        /** @var JsonFile $autoloadJson */
        $autoloadJson = $this->filesystem->get('vendor/autoload.json');
        if (!$autoloadJson->exists()) {
            return $classes;
        }

        try {
            $this->filesystem->includeFile('vendor/autoload.php');
        } catch (IncludeFileException $e) {
            return $classes;
        }

        foreach ($autoloadJson->parse() as $loader) {
            if (class_exists($loader['class'])) {
                /** @var ExtensionInterface $class */
                $class = new $loader['class']();
                if ($class instanceof ExtensionInterface) {
                    $name = $class->getName();
                    $classes[$name] = new ResolvedExtension($class);
                }
            }
        }

        return $classes;
    }
}
