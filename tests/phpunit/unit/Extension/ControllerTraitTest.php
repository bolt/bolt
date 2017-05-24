<?php

namespace Bolt\Tests\Extension;

use Bolt\Events\MountEvent;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\ControllerExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class to test Bolt\Extension\ControllerTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerTraitTest extends BoltUnitTest
{
    public function testRoutesDefault()
    {
        $app = $this->getApp();
        $event = $this->getMockMountEvent();
        $event->expects($this->exactly(2))->method('mount');

        $ext = new NormalExtension();
        $ext->setContainer($app);
        $ext->onMountRoutes($event);
    }

    public function testRoutes()
    {
        $app = $this->getApp();
        $event = $this->getMockMountEvent();
        $event->expects($this->exactly(2))->method('mount');

        $ext = new ControllerExtension();
        $ext->setContainer($app);
        $ext->onMountRoutes($event);
    }

    /**
     * @param array $methods
     *
     * @return MountEvent|MockObject
     */
    protected function getMockMountEvent($methods = ['mount'])
    {
        $app = $this->getApp();

        return $this->getMockBuilder(MountEvent::class)
            ->setMethods($methods)
            ->setConstructorArgs([$app, $app['controllers']])
            ->getMock()
        ;
    }
}
