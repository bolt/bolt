<?php
namespace Bolt\Tests\Configuration;

use Bolt\Configuration\ConfigurationValueProxy;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of the Configuration Proxy class.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigurationProxyTest extends BoltUnitTest
{
    public function testInitialFetch()
    {
        $app = $this->makeApp();
        $configNode = new ConfigurationValueProxy($app['config'], 'contenttypes/pages');
        $app['dispatcher']->addSubscriber($configNode);

        // This asserts that the initial value matches the yml file
        $this->assertEquals('Pages', $configNode['name']);

        // Now we change it, boot the app and make sure the proxy has changed
        $app['config']->set('contenttypes/pages/name', 'Pages Test');
        $app->initialize();
        $this->assertNotEquals('Pages Test', $configNode['name']);
        $app->handle(Request::createFromGlobals());
        $this->assertEquals('Pages Test', $configNode['name']);
    }
}
