<?php

namespace Bolt\Tests\Provider;

use Bolt\Canonical;
use Bolt\Provider\CanonicalServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/CanonicalServiceProvider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CanonicalServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new CanonicalServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(Canonical::class, $app['canonical']);
        $app->boot();
    }
}
