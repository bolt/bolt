<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\NutServiceProvider;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Command\Command;

/**
 * Class to test src/Provider/NutServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class NutServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app->register(new NutServiceProvider());
        $app->boot();

        $this->assertInstanceOf('Symfony\Component\Console\Application', $app['nut']);
        $this->assertInstanceOf('Symfony\Component\Console\Application', $app['console']);
        $this->assertTrue(is_array($app['nut.commands']));
    }

    public function testAddCommandCallableSingle()
    {
        $app = $this->getApp();
        $app->register(new NutServiceProvider());

        $app['nut.commands.add'](function ($app) {
            return new Command('command1');
        });

        $this->assertTrue($app['nut']->has('command1'));
    }

    public function testAddCommandCallableMultiple()
    {
        $app = $this->getApp();
        $app->register(new NutServiceProvider());

        $app['nut.commands.add'](function ($app) {
            return [
                new Command('command1'),
                new Command('command2'),
            ];
        });

        $this->assertTrue($app['nut']->has('command1'));
        $this->assertTrue($app['nut']->has('command2'));
    }

    public function testAddCommandSingle()
    {
        $app = $this->getApp();
        $app->register(new NutServiceProvider());

        $command1 = new Command('command1');
        $app['nut.commands.add']($command1);

        $this->assertTrue($app['nut']->has('command1'));
    }

    public function testAddCommandMultiple()
    {
        $app = $this->getApp();
        $app->register(new NutServiceProvider());

        $command1 = new Command('command1');
        $command2 = new Command('command2');
        $app['nut.commands.add']([$command1, $command2]);

        $this->assertTrue($app['nut']->has('command1'));
        $this->assertTrue($app['nut']->has('command2'));
    }
}
