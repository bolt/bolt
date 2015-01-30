<?php
namespace Bolt\Tests\Provider;

use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\IntegrityCheckerProvider;

/**
 * Class to test src/Provider/IntegrityCheckerProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class IntegrityCheckerProviderTest extends BoltUnitTest
{

    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new IntegrityCheckerProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Database\IntegrityChecker', $app['integritychecker']);
        $app->boot();
    }

}
