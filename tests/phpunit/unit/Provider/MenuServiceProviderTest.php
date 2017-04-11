<?php

namespace Bolt\Tests\Provider;

use Bolt\Menu\MenuBuilder;
use Bolt\Menu\MenuEntry;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\MenuServiceProvider
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MenuServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(MenuBuilder::class, $app['menu']);
        $this->assertInstanceOf(MenuEntry::class, $app['menu.admin']);
    }
}
