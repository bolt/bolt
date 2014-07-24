<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;

class FilesystemSearch implements PluginInterface
{
    
    public $filesystem;

 
    public function getMethod()
    {
        return 'search';
    }


    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    
    
    public function search($term, $extensions = array())
    {
        $allFiles = $this->filesystem->listContents('', true);
        $files = array();
        foreach($allFiles as $file) {
           if(strpos($file['path'], $term) !== false) {
            $files[] = $file['path'];
           } 
        }
    }
    
}