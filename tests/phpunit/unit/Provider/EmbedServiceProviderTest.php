<?php

namespace Bolt\Tests\Provider;

use Bolt\Embed;
use Bolt\Provider\EmbedServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/EmbedServiceProvider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class EmbedServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new EmbedServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(Embed\Resolver::class, $app['embed']);
        $this->assertInstanceOf(Embed\GuzzleDispatcher::class, $app['embed.dispatcher']);
        $this->assertInstanceOf(\Closure::class, $app['embed.factory']);
        $app->boot();
    }
}
