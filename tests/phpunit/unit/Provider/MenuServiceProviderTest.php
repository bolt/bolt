<?php
namespace Bolt\Tests\Provider;

use Bolt\Menu\MenuBuilder;
use Bolt\Menu\MenuEntry;
use Bolt\Provider\MenuServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/MenuServiceProvider
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MenuServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new MenuServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(MenuBuilder::class, $app['menu']);
        $this->assertInstanceOf(MenuEntry::class, $app['menu.admin']);
        $app->boot();
    }
}
