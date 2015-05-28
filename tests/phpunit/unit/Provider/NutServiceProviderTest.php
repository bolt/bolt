<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\NutServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/NutServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class NutServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new NutServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Symfony\Component\Console\Application', $app['nut']);
        $this->assertInstanceOf('Symfony\Component\Console\Application', $app['console']);
        $this->assertTrue(is_array($app['nut.commands']));
        $app->boot();
    }

    public function testAddCommand()
    {
        $app = $this->makeApp();
        $provider = new NutServiceProvider($app);
        $app->register($provider);
        $app->boot();
        $command = $this->getMock('Symfony\Component\Console\Command\Command', null, ['mockCommand']);
        $app['nut.commands.add']($command);
        $this->assertTrue(in_array($command, $app['nut.commands']));
    }
}
