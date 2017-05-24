<?php

namespace Bolt\Tests\Events;

use Bolt\Events\MountEvent;
use Bolt\Tests\BoltUnitTest;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\Route;

/**
 * Class to test Bolt\Events\MountEvent.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MountEventTest extends BoltUnitTest
{
    public function testConstructor()
    {
        $app = $this->getApp();
        $controllers = new ControllerCollection(new Route('/'));
        $mountEvent = new MountEvent($app, $controllers);

        $this->assertInstanceOf(MountEvent::class, $mountEvent);
        $this->assertInstanceOf(Application::class, $mountEvent->getApp());
    }

    public function testMount()
    {
        $app = $this->getApp();

        $route = new Route('/');
        $controllers = $this->getMockControllerCollection(['mount'], $route);
        $controllers
            ->expects($this->once())
            ->method('mount')
        ;

        $mountEvent = new MountEvent($app, $controllers);
        $mountEvent->mount('/', $controllers);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The "mount" method takes either a "ControllerCollection" or a "ControllerProviderInterface" instance.
     */
    public function testMountInvalidCollection()
    {
        $app = $this->getApp();

        $route = new Route('/');
        $controllers = $this->getMockControllerCollection(['connect', 'mount'], $route);
        $controllers
            ->expects($this->never())
            ->method('mount')
        ;

        $mountEvent = new MountEvent($app, $controllers);
        $mountEvent->mount('/', $route);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The method "Bolt\Tests\Events\Mock\ControllerMock::connect" must return a "ControllerCollection" instance. Got: "Bolt\Tests\Events\Mock\ClippyKoala"
     */
    public function testMountInvalidCollectionConnect()
    {
        $app = $this->getApp();

        $route = new Route('/');
        $controllers = $this->getMockControllerCollection(['connect', 'mount'], $route);
        $controllers
            ->expects($this->never())
            ->method('mount')
        ;

        $mountEvent = new MountEvent($app, $controllers);
        $mountEvent->mount('/', new Mock\ControllerMock($route));
    }

    /**
     * @param array $methods
     * @param Route $route
     *
     * @return MockObject|ControllerCollection
     */
    protected function getMockControllerCollection($methods = ['connect', 'mount'], Route $route)
    {
        return $this->getMockBuilder(ControllerCollection::class)
            ->setMethods($methods)
            ->setConstructorArgs([$route])
            ->getMock()
            ;
    }
}
