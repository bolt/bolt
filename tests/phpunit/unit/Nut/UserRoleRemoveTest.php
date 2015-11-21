<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\UserRoleRemove;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/UserRoleRemove.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class UserRoleRemoveTest extends BoltUnitTest
{
    public function testRemove()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, true, true);
        $command = new UserRoleRemove($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => 'admin']);
        $result = $tester->getDisplay();

        $this->assertRegExp('/no longer has role/', trim($result));
    }

    public function testRemoveFail()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, false, true);
        $command = new UserRoleRemove($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => 'admin']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Could not remove role/', trim($result));
    }

    public function testRemoveNonexisting()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, true, false);
        $command = new UserRoleRemove($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => 'admin']);
        $result = $tester->getDisplay();
        $this->assertRegExp("/ already doesn't have role/", trim($result));
    }

    protected function getUserMock($app, $remove = false, $has = false)
    {
        $users = $this->getMock('Bolt\Users', ['hasRole', 'removeRole'], [$app]);
        $users->expects($this->any())
            ->method('removeRole')
            ->will($this->returnValue($remove));

        $users->expects($this->any())
            ->method('hasRole')
            ->will($this->returnValue($has));

        return $users;
    }
}
