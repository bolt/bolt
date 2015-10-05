<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\TwigServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/TwigServiceProvider.
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
        $app->register(new TwigServiceProvider());
        $app->boot();

        $this->assertInstanceOf('\Pimple', $app['twig.handlers']);
        $this->assertNotEmpty($app['twig.handlers']->keys());

        $this->assertNotEmpty($app['twig.path']);
        $this->assertNotEmpty($app['twig.options']['cache'], 'Cache path was not set');
        $this->assertInstanceOf('\Twig_Environment', $app['twig']);
        $this->assertTrue($app['twig']->hasExtension('Bolt'), 'Bolt\Twig\TwigExtension was not added to twig environment');
        $this->assertContains('bolt', $app['twig.loader.filesystem']->getNamespaces(), 'bolt namespace was not added to filesystem loader');

        $this->assertInstanceOf('\Bolt\Twig\TwigExtension', $app['safe_twig.bolt_extension']);
        $this->assertInstanceOf('\Twig_Environment', $app['safe_twig']);
    }

    public function testConfigCacheDisabled()
    {
        $app = $this->getApp();

        $app['config']->set('general/caching/templates', false);
        $app->register(new TwigServiceProvider());
        $this->assertFalse($app['twig.options']['cache']);
    }
}
