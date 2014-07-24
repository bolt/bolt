<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;
use Bolt\Application;


class PublicUrlPlugin implements PluginInterface
{
    
    public $filesystem;
    public $urlName;
    
    public function __construct($urlName)
    {
        $this->urlName = $urlName;
    }

 
    public function getMethod()
    {
        return 'url';
    }


    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    
    
    public function handle(Application $app, $path)
    {
        $prefix = $app['resources']->getUrl($this->urlName);
        return $prefix.$path;
    }
    
}