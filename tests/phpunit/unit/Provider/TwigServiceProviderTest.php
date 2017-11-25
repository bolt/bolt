<?php

namespace Bolt\Tests\Provider;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Extension\BoltExtension;
use Bolt\Twig\SafeEnvironment;
use Twig\Environment;

/**
 * @covers \Bolt\Provider\TwigServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 *
 * @covers \Bolt\Provider\TwigServiceProvider
 */
class TwigServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();

        $this->assertNotEmpty($app['twig.options']['cache'], 'Cache path was not set');
        $this->assertInstanceOf(Environment::class, $app['twig']);
        $this->assertTrue($app['twig']->hasExtension(BoltExtension::class), 'Bolt\Twig\Extension\BoltExtension was not added to twig environment');
        $this->assertContains('bolt', $app['twig.loader.bolt_filesystem']->getNamespaces(), 'bolt namespace was not added to filesystem loader');
    }

    /**
     * @group legacy
     */
    public function testLegacyProvider()
    {
        $app = $this->getApp();

        $this->assertInstanceOf(SafeEnvironment::class, $app['safe_twig']);
    }

    public function testConfigCacheDisabled()
    {
        $app = $this->getApp(false);
        $app['config']->set('general/caching/templates', false);
        $this->assertArrayNotHasKey('cache', $app['twig.options']);
    }
}
