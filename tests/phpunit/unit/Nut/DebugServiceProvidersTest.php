<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\DebugServiceProviders;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for \Bolt\Nut\DebugServiceProviders.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugServiceProvidersTest extends BoltUnitTest
{
    use TableHelperTrait;

    protected $regexExpectedA = '/(Silex.Provider.DoctrineServiceProvider).+(\d+)/';
    protected $regexExpectedB = '/(Bolt.Provider.AssetServiceProvider).+(\d+)/';

    public function testRunNormal()
    {
        $result = $this->getNormalOuput();

        $this->assertRegExp($this->regexExpectedA, $result);
        $this->assertRegExp($this->regexExpectedB, $result);
    }

    public function testSortClass()
    {
        $tester = $this->getCommandTester();

        $expectedOutput = $this->getNormalOuput();
        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $expectedOutput);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $expectedOutput);
        $this->assertGreaterThan($expectedA, $expectedB);

        $tester->execute(['--sort-class' => true]);
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
        $command = new DebugServiceProviders($app);

        return new CommandTester($command);
    }
}
