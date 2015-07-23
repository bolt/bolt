<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\SessionServiceProvider;
use Bolt\Session\FileSessionHandler;
use Bolt\Session\Generator\NativeGenerator;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionStorage;
use Bolt\Session\SessionListener;
use Bolt\Tests\BoltUnitTest;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Class to test src/Provider/SessionServiceProvider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SessionServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new SessionServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Session\Generator\RandomGenerator',                          $app['session.storage.generator']);
        $this->assertInstanceOf('Bolt\Session\Serializer\NativeSerializer',                        $app['session.storage.serializer']);
        $this->assertInstanceOf('Pimple',                                                          $app['sessions']);
        $this->assertInstanceOf('Pimple',                                                          $app['sessions.listener']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag', $app['session.bag.attribute']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Flash\FlashBag',         $app['session.bag.flash']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Session',                $app['session']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Storage\MetadataBag',    $app['session.bag.metadata']);

        $this->assertArrayHasKey('main',    $app['sessions.options']);
        $this->assertArrayHasKey('csrf',    $app['sessions.options']);
        $this->assertArrayHasKey('handler', $app['session.default_options']);

        $this->assertSame('main', $app['sessions.default']);

        $app->boot();
    }
}
