<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\OmnisearchServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/OmnisearchServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class OmnisearchServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new OmnisearchServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Omnisearch', $app['omnisearch']);
        $app->boot();
    }
}
