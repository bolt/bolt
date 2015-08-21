<?php 
namespace Bolt\Storage;

use Bolt\Application;

/**
* 
*/
class ContentLegacyService
{
    
    protected $app;
    
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}