<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\MenuServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/MenuServiceProvider
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MenuServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new MenuServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Menu\MenuBuilder', $app['menu']);
        $this->assertInstanceOf('Bolt\Menu\MenuEntry', $app['menu.admin']);
        $app->boot();
    }
}
