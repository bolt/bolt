<?php

namespace Bolt\Tests\Extension;

use Bolt\Events\ControllerEvents;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\NormalExtension;

/**
 * Class to test Bolt\Extension\SimpleExtension
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SimpleExtensionTest extends BoltUnitTest
{
    public function testRegister()
    {
        $app = $this->getApp();
        $mock = $this->getMockBuilder(NormalExtension::class)
            ->setMethods(['getContainer'])
            ->getMock()
        ;
        $mock->expects($this->atLeast(4))->method('getContainer')->willReturn($app);

        /** @var NormalExtension $mock */
        $mock->setContainer($app);
        $mock->register($app);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Drop Bear Alert!
     */
    public function testSubscribe()
    {
        $app = $this->getApp();
        $ext = new NormalExtension();
        $ext->setContainer($app);
        $ext->boot($app);

        $listeners = $app['dispatcher']->getListeners('dropbear.sighting');
        $this->assertInstanceOf('Bolt\Tests\Extension\Mock\NormalExtension', $listeners[0][0]);

        $app['dispatcher']->dispatch('dropbear.sighting');
    }

    public function testGetServiceProvider()
    {
        $ext = new NormalExtension();

        $providers = $ext->getServiceProviders();
        $this->assertInstanceOf('Bolt\Extension\AbstractExtension', $providers[0]);
        $this->assertInstanceOf('Silex\ServiceProviderInterface', $providers[0]);
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', $providers[0]);
    }

    public function testGetSubscribedEvents()
    {
        $ext = new NormalExtension();
        $events = $ext->getSubscribedEvents();
        $expected = [
            ControllerEvents::MOUNT => [
                ['onMountRoutes', 0],
                ['onMountControllers', 0],
            ],
        ];

        $this->assertSame($expected, $events);
    }
}
