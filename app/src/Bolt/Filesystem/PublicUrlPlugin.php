<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;
use Bolt\Application;


class PublicUrlPlugin implements PluginInterface
{
    
    public $filesystem;
    public $namespace;
    public $handlers = array();
    
    public function __construct(Application $app, $namespace)
    {
        $this->app = $app;
        if($namespace == "default") {
            $this->namespace = 'files';
        } else {
            $this->namespace = $namespace;
        }
        
    }

 
    public function getMethod()
    {
        return 'url';
    }


    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    
    
    public function handle($path)
    {
        
        switch($this->adapterType()) {
            default: 
                return $this->getLocalUrl($path);
        }
        
    }
    
    public function getLocalUrl($path)
    {
        $prefix = $this->app['resources']->getUrl($this->namespace);
        return $prefix.$path;
    }
    
    
    protected function adapterType()
    {
        $reflect = new \ReflectionClass($this->filesystem->getAdapter());
        return strtolower($reflect->getShortName());
    }
    
    
    
    
}