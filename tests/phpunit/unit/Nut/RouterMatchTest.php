<?php

namespace Bolt\Tests\Nut;

use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for \Bolt\Nut\RouterMatch.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RouterMatchTest extends BoltUnitTest
{
    /**
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Not enough arguments (missing: "path_info").
     */
    public function testRunNormal()
    {
        $tester = $this->getCommandTester();

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Not enough arguments/', $result);
    }

    public function providerRunPaths()
    {
        return [
            [
                '/',
                '/Route \"homepage\" matches/',
                '/(Route Name).+(homepage)/',
                '/(Path).+(\/)/',
            ],
            [
                '/bolt/editcontent/pages/42',
                '/Route \"editcontent\" matches/',
                '/(Route Name).+(editcontent)/',
                '/(Path).+(\/bolt\/editcontent\/{contenttypeslug}\/{id})/',
            ],
            [
                '/pages/koalas',
                '/Route \"contentlink\" matches/',
                '/(Route Name).+(contentlink)/',
                '/(Path).+(\/{contenttypeslug}\/{slug})/',
            ],
        ];
    }

    /**
     * @dataProvider providerRunPaths
     *
     * @param string $uri
     * @param string $confirmation
     * @param string $routeName
     * @param string $path
     */
    public function testRunPaths($uri, $confirmation, $routeName, $path)
    {
        $tester = $this->getCommandTester();

        $tester->execute(['path_info' => $uri]);
        $result = $tester->getDisplay();
        ConsoleCommandEvent::RETURN_CODE_DISABLED;

        $this->assertRegExp($confirmation, $result);
        $this->assertRegExp($routeName, $result);
        $this->assertRegExp($path, $result);
    }

    /**
     * @return CommandTester
     */
    protected function getCommandTester()
    {
        $app = $this->getApp();
        $command = $app['nut']->get('router:match');

        return new CommandTester($command);
    }
}
