<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\TwigServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/TwigServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TwigServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new TwigServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Twig_Environment', $app['twig']);
        $this->assertNotEmpty($app['twig.path']);
        $this->assertNotEmpty($app['twig.options']['cache']);
        $app['config']->set('general/caching/templates', false);
        $app->register($provider);
        $this->assertFalse($app['twig.options']['cache']);
        $app->boot();
    }
}
