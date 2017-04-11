<?php

namespace Bolt\Tests\Provider;

use Bolt\Storage\Database\Schema;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\DatabaseSchemaServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseSchemaServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Schema\Manager::class, $app['schema']);
    }
}
