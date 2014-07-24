<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;
use Bolt\Application;

class BrowsePlugin implements PluginInterface
{
    
    public $filesystem;

 
    public function getMethod()
    {
        return 'browse';
    }


    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    
    
    public function handle($path, Application $app)
    {
        
        $list = $this->filesystem->listContents($path);
        
        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        foreach($list as $entry) {

            if (in_array($entry['basename'], $ignored)) {
                continue;
            }

            $fullfilename = $filesystem->getAdapter()->applyPathPrefix($entry['path']);


            if (! $app['filepermissions']->authorized($fullfilename) ) {
                continue;
            }
                
            if($entry['type']==='file') {

                $files[$entry['path']] = array(
                    'path' => $entry['dirname'],
                    'filename' => $entry['basename'],
                    'newpath' => $entry['path'],
                    'writable' => true,
                    'readable' => true,
                    'type' => $entry['extension'],
                    'filesize' => formatFilesize($entry['size']),
                    'modified' => date("Y/m/d H:i:s", $entry['timestamp']),
                    'permissions' => $filesystem->getVisibility($entry['path'])
                );
                
                /***** Extra checks for files that can be resolved via PHP urlopen functions *****/
                if(is_readable($fullfilename)) {
                    if (in_array($entry['extension'], array('gif', 'jpg', 'png', 'jpeg'))) {
                        $size = getimagesize($fullfilename);
                        $files[$entry['path']]['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                    }
                    
                    $files[$entry['path']]['permissions'] = \utilphp\util::full_permissions($fullfilename);
                }

                
            }

            if($entry['type']=='dir') {
                $folders[$entry['path']] = array(
                    'path' => $entry['dirname'],
                    'foldername' => $entry['basename'],
                    'newpath' => $entry['path'],
                    'modified' => date("Y/m/d H:i:s", $entry['timestamp'])
                );
                
                /***** Extra checks for files that can be resolved via PHP urlopen functions *****/
                if(is_readable($fullfilename)) {
                    $folders[$entry['path']]['writable'] = true;
                }
            }                  

        }
    }
    
}