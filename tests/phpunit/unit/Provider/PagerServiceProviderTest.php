<?php

namespace Bolt\Tests\Provider;

use Bolt\Pager\PagerManager;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\PagerServiceProvider
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(PagerManager::class, $app['pager']);
    }
}
