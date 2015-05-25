<?php
namespace Bolt\Tests\Provider;

use Bolt\AccessControl\Permissions;
use Bolt\Provider\PermissionsServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/PermissionsServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PermissionsServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new PermissionsServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\AccessControl\Permissions', $app['permissions']);
        $app->boot();
    }
}
