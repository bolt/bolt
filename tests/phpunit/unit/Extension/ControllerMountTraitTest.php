<?php

namespace Bolt\Tests\Extension;

use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\ControllerMountExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\ControllerMountTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerMountTraitTest extends BoltUnitTest
{
    public function testControllerMountDefault()
    {
        $app = $this->getApp();
        $event = $this->getMock('Bolt\Events\MountEvent', ['mount'], [$app, $app['controllers']]);
        $event->expects($this->exactly(0))->method('mount');

        $ext = new NormalExtension();
        $ext->setContainer($app);
        $ext->onMountControllers($event);
    }

    public function testControllerMount()
    {
        $app = $this->getApp();
        $event = $this->getMock('Bolt\Events\MountEvent', ['mount'], [$app, $app['controllers']]);
        $event->expects($this->exactly(2))->method('mount');

        $ext = new ControllerMountExtension();
        $ext->setContainer($app);
        $ext->onMountControllers($event);
    }
}
