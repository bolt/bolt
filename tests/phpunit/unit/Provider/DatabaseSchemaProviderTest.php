<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\DatabaseSchemaProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/DatabaseSchemaProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseSchemaProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new DatabaseSchemaProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Storage\Database\Schema\Manager', $app['schema']);
        $app->boot();
    }
}
