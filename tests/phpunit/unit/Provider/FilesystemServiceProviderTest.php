<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\FilesystemServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test Bolt\Provider\FilesystemServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();

        $app->register(new FilesystemServiceProvider());
        $this->assertInstanceOf('Bolt\Filesystem\Manager', $app['filesystem']);

        $app->boot();
    }
}
