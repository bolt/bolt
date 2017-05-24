<?php

namespace Bolt\Tests\Extension;

use Bolt\Events\MountEvent;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\ControllerMountExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class to test Bolt\Extension\ControllerMountTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerMountTraitTest extends BoltUnitTest
{
    public function testControllerMountDefault()
    {
        $app = $this->getApp();
        $event = $this->getMockMountEvent();
        $event->expects($this->exactly(0))->method('mount');

        $ext = new NormalExtension();
        $ext->setContainer($app);
        $ext->onMountControllers($event);
    }

    public function testControllerMount()
    {
        $app = $this->getApp();
        $event = $this->getMockMountEvent();
        $event->expects($this->exactly(2))->method('mount');

        $ext = new ControllerMountExtension();
        $ext->setContainer($app);
        $ext->onMountControllers($event);
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
