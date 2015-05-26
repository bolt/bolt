<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\DatabaseRepair;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/DatabaseRepair.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseRepairTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new DatabaseRepair($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertEquals("Your database is already up to date.", trim($result));

        // Now introduce some changes
        $app['config']->set('contenttypes/newcontent', [
            'tablename' => 'newcontent',
            'fields'    => ['title' => ['type' => 'text']]
        ]);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp("/Created table `bolt_newcontent`/", $result);
    }
}
