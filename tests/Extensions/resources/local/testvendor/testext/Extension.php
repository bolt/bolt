<?php
use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;
use Bolt\BaseExtension;

class MockLocalExtension extends BaseExtension implements ExtensionInterface
{
    
    public function __construct(Application $app) 
    {
        $this->app = $app;
    }
    
    public function getName()
    {
        return "testlocal";
    }


}