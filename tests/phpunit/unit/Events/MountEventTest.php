<?php

namespace Bolt\Tests\Events;

use Bolt\Events\MountEvent;
use Bolt\Tests\BoltUnitTest;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\Route;

/**
 * Class to test Bolt\Events\MountEvent
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

        $this->assertInstanceOf('Bolt\Events\MountEvent', $mountEvent);
        $this->assertInstanceOf('Silex\Application', $mountEvent->getApp());
    }

    public function testMount()
    {
        $app = $this->getApp();

        $route = new Route('/');
        $controllers = $this->getMock('Silex\ControllerCollection', ['mount'], [$route]);
        $controllers
            ->expects($this->once())
            ->method('mount')
        ;

        $mountEvent = new MountEvent($app, $controllers);
        $mountEvent->mount('/', $controllers);
    }

    public function testMountInvalidCollection()
    {
        $app = $this->getApp();

        $route = new Route('/');
        $controllers = $this->getMock('Silex\ControllerCollection', ['connect', 'mount'], [$route]);
        $controllers
            ->expects($this->never())
            ->method('mount')
        ;

        $this->setExpectedException('LogicException', 'The "mount" method takes either a "ControllerCollection" or a "ControllerProviderInterface" instance.');
        $mountEvent = new MountEvent($app, $controllers);
        $mountEvent->mount('/', $route);
    }

    public function testMountInvalidCollectionConnect()
    {
        $app = $this->getApp();

        $route = new Route('/');
        $controllers = $this->getMock('Silex\ControllerCollection', ['connect', 'mount'], [$route]);
        $controllers
            ->expects($this->never())
            ->method('mount')
        ;

        $this->setExpectedException('LogicException', 'The method "Bolt\Tests\Events\ControllerMock::connect" must return a "ControllerCollection" instance. Got: "Bolt\Tests\Events\ClippyKoala"');

        $mountEvent = new MountEvent($app, $controllers);
        $mountEvent->mount('/', new ControllerMock($route));
    }
}

class ControllerMock implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        return new ClippyKoala();
    }
}

class ClippyKoala
{
}
