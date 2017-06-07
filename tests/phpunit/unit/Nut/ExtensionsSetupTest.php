<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\ExtensionsSetup;
use Bolt\Nut\Helper\ContainerHelper;
use PHPUnit\Framework\TestCase;
use Pimple as Container;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Bolt\Nut\ExtensionsSetup
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsSetupTest extends TestCase
{
    public function testRun()
    {
        $jsonMock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['update'])
            ->getMock()
        ;
        $jsonMock->expects($this->once())->method('update')->willReturn(0);
        $actionMock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['execute'])
            ->getMock()
        ;
        $actionMock->expects($this->once())->method('execute')->willReturn(0);
        $ioMock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getOutput'])
            ->getMock()
        ;
        $ioMock->expects($this->once())->method('getOutput');

        $container = new Container();
        $container['extend.manager.json'] = $jsonMock;
        $container['extend.action'] = ['autoload' => $actionMock];
        $container['extend.action.io'] = $ioMock;

        $helper = new ContainerHelper($container);
        $command = new ExtensionsSetup();
        $command->setHelperSet(new HelperSet([$helper]));
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Creating\/updating composer.json… \[DONE\]/', $result);
        $this->assertRegExp('/Updating autoloaders… \[DONE\]/', $result);
    }
}
