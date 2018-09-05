<?php

namespace Bolt\Tests\Extension;

use Bolt\Events\ControllerEvents;
use Bolt\Extension\AbstractExtension;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Extension\Mock\NormalExtension;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class to test Bolt\Extension\SimpleExtension.
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
        $this->assertInstanceOf(NormalExtension::class, $listeners[0][0]);

        $app['dispatcher']->dispatch('dropbear.sighting');
    }

    public function testGetServiceProvider()
    {
        $ext = new NormalExtension();

        $providers = $ext->getServiceProviders();
        $this->assertInstanceOf(AbstractExtension::class, $providers[0]);
        $this->assertInstanceOf(ServiceProviderInterface::class, $providers[0]);
        $this->assertInstanceOf(EventSubscriberInterface::class, $providers[0]);
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

    public function testStorageTraitHasBeenImported()
    {
        $ext = new NormalExtension();
        $storageTraitHasBeenImported = method_exists($ext, 'extendRepositoryMapping');

        $this->assertTrue($storageTraitHasBeenImported);
    }
}
