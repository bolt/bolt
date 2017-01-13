<?php
namespace Bolt\Tests\Controller;

use Bolt\Configuration\Validation\Validator;
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
        $this->getApp()->offsetGet('request_stack')->push($request);
    }

    /**
     * @return Request $request
     */
    protected function getRequest()
    {
        return $this->getApp()->offsetGet('request');
    }

    protected function getApp($boot = true)
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

        $verifier = new Validator(
            $app['controller.exception'],
            $app['config'],
            $app['resources'],
            $app['logger.flash']
        );
        $verifier->checks();

        $app->boot();

        return $app;
    }

    protected function getFlashBag()
    {
        return $this->getService('logger.flash');
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->app = null;
    }

    abstract protected function controller();
}
