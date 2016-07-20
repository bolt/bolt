<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\ConfigServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/ConfigServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new ConfigServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Config', $app['config']);
        $app->boot();
    }
}
