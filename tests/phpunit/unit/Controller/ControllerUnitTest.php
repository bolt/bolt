<?php
namespace Bolt\Tests\Controller;

use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

class ControllerUnitTest extends BoltUnitTest
{
    private $app;

    protected function setRequest(Request $request)
    {
        $this->getApp()->offsetSet('request', $request);
    }

    protected function getService($key)
    {
        return $this->getApp()->offsetGet($key);
    }

    protected function getApp()
    {
        if (!$this->app) {
            $this->app = $this->makeApp();
        }
        return $this->app;
    }

    protected function makeApp()
    {
        $app = parent::makeApp();
        $app->initialize();
        $app['twig.loader'] = new \Twig_Loader_Chain(array(new \Twig_Loader_String()));
        $app->boot();

        return $app;
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->app = null;
    }
}
