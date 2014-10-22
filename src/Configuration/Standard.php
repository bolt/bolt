<?php
namespace Bolt\Configuration;

use Bolt\Application;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Composer\Autoload\ClassLoader;

/**
 * Left as a blank extension of ResourceManager for now, this semantically represents a default configuration
 * for a Bolt application.
 */
class Standard extends ResourceManager
{

    public function __construct($loader)
    {
        $container = new \Pimple();

        if ($loader instanceof ClassLoader) {
            $container['classloader'] = $loader;
        } else {
            $container['rootpath'] = $loader;
        }

        $container['pathmanager'] = new PlatformFileSystemPathFactory();

        parent::__construct($container);
    }
}
