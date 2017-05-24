<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\DebugEvents;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test for \Bolt\Nut\DebugEvents.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugEventsTest extends BoltUnitTest
{
    use TableHelperTrait;

    protected $regexExpectedA = '/(#\d+).+(Symfony.Component.HttpKernel.EventListener.RouterListener::onKernelRequest).+(32)/';
    protected $regexExpectedB = '/(Bolt.Routing.Canonical::onRequest).+(31)/';

    public function testRunNormal()
    {
        $tester = $this->getCommandTester();

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp($this->regexExpectedA, $result);
        $this->assertRegExp($this->regexExpectedB, $result);
    }

    public function testSortListener()
    {
        $tester = $this->getCommandTester();

        $expectedOutput = $this->getNormalOuput();
        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $expectedOutput);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $expectedOutput);
        $this->assertGreaterThan($expectedA, $expectedB);

        $tester->execute(['--sort-listener' => true]);
        $result = $tester->getDisplay();

        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $result);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $result);
        $this->assertLessThan($expectedA, $expectedB);
    }

    /**
     * @return CommandTester
     */
    protected function getCommandTester()
    {
        $app = $this->getApp();
        $command = new DebugEvents($app);

        return new CommandTester($command);
    }
}
