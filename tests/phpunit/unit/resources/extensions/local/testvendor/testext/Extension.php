<?php
use Bolt\Application;
use Bolt\BaseExtension;

class MockLocalExtension extends BaseExtension
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function initialize()
    {
    }
}
