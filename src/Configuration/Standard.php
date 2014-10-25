<?php
namespace Bolt\Configuration;

use Bolt\Application;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Left as a blank extension of ResourceManager for now, this semantically represents a default configuration
 * for a Bolt application.
 */
class Standard extends ResourceManager
{

    public function __construct($loader, Request $request = null)
    {
        $container = new \Pimple();

        if ($loader instanceof ClassLoader) {
            $container['classloader'] = $loader;
        } else {
            $container['rootpath'] = $loader;
        }

        $container['pathmanager'] = new PlatformFileSystemPathFactory();
        $container['request'] = $request;

        parent::__construct($container);
    }
}
