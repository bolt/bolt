<?php
use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;

class MockLocalExtension implements ExtensionInterface
{
    
    public function __construct(Application $app) 
    {
        $this->app = $app;
    }
    
    public function getName()
    {
        return "testlocal";
    }
    
    
    
    public function initialize() 
    {
        
    }
    
    public function getConfig()
    {
        
    }
    
    public function getSnippets()
    {
        
    }
    
    public function getExtensionConfig()
    {
        
    }
}