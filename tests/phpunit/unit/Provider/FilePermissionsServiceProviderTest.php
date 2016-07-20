<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\FilePermissionsServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/FilePermissionsServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FilePermissionsServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new FilePermissionsServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Filesystem\FilePermissions', $app['filepermissions']);
        $app->boot();
    }
}
