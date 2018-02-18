<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\UsersList;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Bolt\Nut\UsersList
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UsersListTest extends BoltUnitTest
{
    public function testListing()
    {
        $app = $this->getApp();
        $this->resetDb();
        $this->addNewUser($app, 'koala', 'Kenny Koala', 'editor', true);
        $this->addNewUser($app, 'bruce', 'Bruce D. Dropbear', 'admin', true);

        $command = new UsersList($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('#koala(\s*).*(\s*)koala@example\.com(\s*).*(\s*)Kenny Koala(\s*)editor(\s*)\d*(\s*).*(\s*)true#', $result);
        $this->assertRegExp('#bruce(\s*).*(\s*)bruce@example\.com(\s*).*(\s*)Bruce D. Dropbear(\s*)admin(\s*)\d*(\s*).*(\s*)true#', $result);
    }
}
