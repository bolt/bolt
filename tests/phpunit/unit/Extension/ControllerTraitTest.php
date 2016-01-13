<?php

namespace Bolt\Tests\Extension;

use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\ControllerExtension;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\ControllerTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerTraitTest extends BoltUnitTest
{
    public function testRoutesDefault()
    {
        $app = $this->getApp();
        $event = $this->getMock('Bolt\Events\MountEvent', ['mount'], [$app, $app['controllers']]);
        $event->expects($this->exactly(2))->method('mount');

        $ext = new NormalExtension();
        $ext->setContainer($app);
        $ext->onMountRoutes($event);
    }

    public function testRoutes()
    {
        $app = $this->getApp();
        $event = $this->getMock('Bolt\Events\MountEvent', ['mount'], [$app, $app['controllers']]);
        $event->expects($this->exactly(2))->method('mount');

        $ext = new ControllerExtension();
        $ext->setContainer($app);
        $ext->onMountRoutes($event);
    }
}
