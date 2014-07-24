<?php

namespace Bolt\Filesystem;

use Silex\Application;
use League\Flysystem\Adapter\Local as FilesystemAdapter;
use League\Flysystem\Filesystem;

/**
* 
*/
class Manager
{
    
    
    public $app;
    public $managers = array();
    
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->managers['default'] = new Filesystem( new FilesystemAdapter($app['resources']->getPath('files')) );
        //$this->managers['default']->addPlugin(new PublicUrlPlugin('files'));
        
        $this->managers['config'] = new Filesystem( new FilesystemAdapter($app['resources']->getPath('config')) );
    }
    
    
    public function getManager($namespace = null)
    {
        if(isset($this->managers[$namespace])) {
            $manager = $this->managers[$namespace];
        } else {
            $manager = $this->managers['default'];
        }
        
        $manager->addPlugin(new SearchPlugin);
        $manager->addPlugin(new BrowsePlugin);
        return $manager;
    }
    
    public function setManager($namespace, $manager)
    {
        $this->managers[$namespace] = $manager;
    }
    
    
    
    
    
    
}