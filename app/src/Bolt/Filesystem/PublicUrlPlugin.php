<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;
use Bolt\Application;


class PublicUrlPlugin implements PluginInterface
{
    
    public $filesystem;

 
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
        
        var_dump(get_class($this->filesystem->adapter)); exit;
        switch(get_class($this->filesystem->adapter)) {
            
        }
        
    }
    
}