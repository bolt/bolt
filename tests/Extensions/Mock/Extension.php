<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;
use Bolt\Extensions\Snippets\Location as SnippetLocation;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class Extension implements ExtensionInterface
{


    public function __construct(Application $app) 
    {
        $this->app = $app;
    }
    
    public function initialize() 
    {
        
    }
    
    public function getConfig()
    {
        
    }
    
    public function getSnippets()
    {
        return array(array(SnippetLocation::END_OF_HEAD, '<meta name="test-snippet" />'));
    }
    
    public function getExtensionConfig()
    {
        
    }
    
    public function getName()
    {
        return "testext";
    }

   
}
