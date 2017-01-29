<?php

namespace Bolt\Configuration;

use Composer\Autoload\ClassLoader;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Left as a blank extension of ResourceManager for now, this semantically
 * represents a default configuration for a Bolt application.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Standard extends ResourceManager
{
    /**
     * @param ClassLoader|string  $loader              ClassLoader or root path
     * @param Request             $request
     * @param PathResolverFactory $pathResolverFactory
     */
    public function __construct($loader, Request $request = null, PathResolverFactory $pathResolverFactory = null)
    {
        $container = new \Pimple();

        if ($loader instanceof ClassLoader) {
            $container['classloader'] = $loader;
        } else {
            $container['rootpath'] = $loader;
        }

        $container['path_resolver_factory'] = $pathResolverFactory;
        $container['pathmanager'] = new PlatformFileSystemPathFactory();
        $container['request'] = $request;

        parent::__construct($container);

        $this->setPath('web', '');
        $this->setPath('view', 'app/view');
    }
}
