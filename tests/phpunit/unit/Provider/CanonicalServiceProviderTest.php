<?php

namespace Bolt\Tests\Provider;

use Bolt\Routing\Canonical;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\CanonicalServiceProvider
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CanonicalServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Canonical::class, $app['canonical']);
    }
}
