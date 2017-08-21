<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\DumpRuntime;
use Bolt\Users;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Class to test Bolt\Twig\Runtime\DumpRuntime.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumpRuntimeTest extends BoltUnitTest
{
    public function dumpBacktraceProvider()
    {
        return [
            'debug off, logged off, no debug while logged off' => [false, false, false, false],
            'debug off, logged off, debug while logged off'    => [false, false, true,  false],
            'debug off, logged on,  no debug while logged off' => [false, true,  false, false],
            'debug off, logged on,  debug while logged off'    => [false, true,  true,  false],
            'debug on,  logged off, no debug while logged off' => [true,  false, false, false],
            'debug on,  logged off, debug while logged off'    => [true,  false, true,  true],
            'debug on,  logged on,  no debug while logged off' => [true,  true,  false, true],
            'debug on,  logged on,  debug while logged off'    => [true,  true,  true,  true],
        ];
    }

    /**
     * @dataProvider dumpBacktraceProvider
     *
     * @param $debug
     * @param $hasUser
     * @param $debugWhileLoggedOff
     * @param $expectOutput
     */
    public function testDumpBacktrace($debug, $hasUser, $debugWhileLoggedOff, $expectOutput)
    {
        $twig = new Environment(new ArrayLoader(), [
            'debug' => $debug,
        ]);

        $users = $this->getMockBuilder(Users::class)
            ->setMethods(['getCurrentUser'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $users->expects($this->any())
            ->method('getCurrentUser')
            ->willReturn($hasUser ? true : null);

        /** @var DumpRuntime|MockObject $runtime */
        $runtime = $this->getMockBuilder(DumpRuntime::class)
            ->setMethods(['dump'])
            ->setConstructorArgs([
                new VarCloner(),
                new HtmlDumper(),
                $users,
                $debugWhileLoggedOff,
            ])
            ->getMock()
        ;
        $runtime->expects($expectOutput ? $this->once() : $this->never())
            ->method('dump')
            ->willReturnArgument(2);

        $actual = $runtime->dumpBacktrace($twig, [], 5);

        if (!$expectOutput) {
            $this->assertNull($actual);

            return;
        }

        $this->assertCount(5, $actual);
        $this->assertArrayHasKey('file', $actual[0]);
        $this->assertArrayHasKey('line', $actual[0]);
        $this->assertArrayHasKey('function', $actual[0]);
        $this->assertArrayHasKey('class', $actual[0]);
        $this->assertArrayHasKey('object', $actual[0]);
    }
}
