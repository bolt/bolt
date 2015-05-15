<?php
namespace Bolt\Tests\Controller;

use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

abstract class ControllerUnitTest extends BoltUnitTest
{
    private $app;

    protected function setUp()
    {
        $this->resetDb();
        $this->addDefaultUser($this->getApp());
        $this->addSomeContent();
    }

    protected function setRequest(Request $request)
    {
        $this->getApp()->offsetSet('request', $request);
    }

    /**
     * @return Request $request
     */
    protected function getRequest()
    {
        return $this->getApp()->offsetGet('request');
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    protected function setService($key, $value)
    {
        $this->getApp()->offsetSet($key, $value);
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

    protected function getFlashBag()
    {
        $this->getService('logger.flash')->flush($this->getService('session')->getFlashBag());
        return $this->getService('session')->getFlashBag();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->app = null;
    }

    abstract protected function controller();
}
