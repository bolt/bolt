<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\DebugRouter;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for \Bolt\Nut\DebugRouter.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugRouterTest extends BoltUnitTest
{
    use TableHelperTrait;

    protected $regexExpectedA = '/(preview).+(POST).+(ANY).+(ANY).+(\/{contenttypeslug})/';
    protected $regexExpectedB = '/(contentaction).+(POST).+(ANY).+(ANY).+(\/async\/content\/action)/';

    public function testRunNormal()
    {
        $tester = $this->getCommandTester();

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp($this->regexExpectedA, $result);
        $this->assertRegExp($this->regexExpectedB, $result);

        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $result);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $result);
        $this->assertLessThan($expectedA, $expectedB);
    }

    public function providerRunNamed()
    {
        return [
            [
                'preview',
                '/(Route Name).+(preview)/',
                '/(Path).+(\/preview\/{contenttypeslug})/',
            ],
            [
                'contentaction',
                '/(Route Name).+(contentaction)/',
                '/(Path).+(\/async\/content\/action)/',
            ],
        ];
    }

    /**
     * @dataProvider providerRunNamed
     *
     * @param string $name
     * @param string $routeNamePattern
     * @param string $pathPattern
     */
    public function testRunNamed($name, $routeNamePattern, $pathPattern)
    {
        $tester = $this->getCommandTester();

        $tester->execute(['name' => $name]);
        $result = $tester->getDisplay();

        $this->assertRegExp($routeNamePattern, $result);
        $this->assertRegExp($pathPattern, $result);
        $this->assertNotRegExp($this->regexExpectedA, $result);
        $this->assertNotRegExp($this->regexExpectedB, $result);
    }

    public function testSortRoute()
    {
        $tester = $this->getCommandTester();
        $tester->execute(['--sort-route' => true]);
        $result = $tester->getDisplay();

        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $result);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $result);
        $this->assertLessThan($expectedA, $expectedB);
    }

    public function testSortPattern()
    {
        $tester = $this->getCommandTester();
        $tester->execute(['--sort-pattern' => true]);
        $result = $tester->getDisplay();

        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $result);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $result);
        $this->assertLessThan($expectedA, $expectedB);
    }

    public function testSortMethod()
    {
        $tester = $this->getCommandTester();
        $tester->execute(['--sort-method' => true]);
        $result = $tester->getDisplay();

        $expectedA = $this->getMatchingLineNumber($this->regexExpectedA, $result);
        $expectedB = $this->getMatchingLineNumber($this->regexExpectedB, $result);
        $this->assertGreaterThan($expectedA, $expectedB);
    }

    /**
     * @return CommandTester
     */
    protected function getCommandTester()
    {
        $app = $this->getApp();
        $command = new DebugRouter($app);

        return new CommandTester($command);
    }
}
