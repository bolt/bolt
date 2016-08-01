<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\SessionServiceProvider;
use Bolt\Session\Generator\GeneratorInterface;
use Bolt\Session\Serializer\SerializerInterface;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * Class to test src/Provider/SessionServiceProvider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app->register(new SessionServiceProvider());

        $this->assertInstanceOf(AttributeBagInterface::class, $app['session.bag.attribute']);
        $this->assertInstanceOf(FlashBagInterface::class, $app['session.bag.flash']);
        $this->assertInstanceOf(MetadataBag::class, $app['session.bag.metadata']);
        $this->assertInstanceOf(GeneratorInterface::class, $app['session.generator']);
        $this->assertInstanceOf(SerializerInterface::class, $app['session.serializer']);

        $this->assertInstanceOf(SessionStorageInterface::class, $app['session.storage']);
        $this->assertInstanceOf(SessionInterface::class, $app['session']);
        $this->assertInstanceOf(EventSubscriberInterface::class, $app['session.listener']);

        $this->assertArrayHasKey('name', $app['session.options']);
        $this->assertArrayHasKey('restrict_realm', $app['session.options']);
        $this->assertArrayHasKey('cookie_lifetime', $app['session.options']);
        $this->assertArrayHasKey('cookie_path', $app['session.options']);
        $this->assertArrayHasKey('cookie_domain', $app['session.options']);
        $this->assertArrayHasKey('cookie_secure', $app['session.options']);
        $this->assertArrayHasKey('cookie_httponly', $app['session.options']);

        $app->boot();
    }
}
