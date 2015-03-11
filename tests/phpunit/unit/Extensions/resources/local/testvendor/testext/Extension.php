<?php
use Bolt\Application;
use Bolt\BaseExtension;
use Bolt\Extensions\ExtensionInterface;

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

    public function initialize()
    {
    }
}
