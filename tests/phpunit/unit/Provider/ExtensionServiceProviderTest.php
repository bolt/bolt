<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\ExtensionServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/ExtensionServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new ExtensionServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Extensions', $app['extensions']);
        $this->assertInstanceOf('Bolt\Composer\Satis\StatService', $app['extensions.stats']);
        $app->boot();
    }
}
