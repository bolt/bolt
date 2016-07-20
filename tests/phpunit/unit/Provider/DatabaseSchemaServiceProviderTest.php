<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\DatabaseSchemaServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/DatabaseSchemaServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseSchemaServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new DatabaseSchemaServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Storage\Database\Schema\Manager', $app['schema']);
        $app->boot();
    }
}
