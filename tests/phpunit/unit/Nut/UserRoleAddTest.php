<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\UserRoleAdd;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/UserRoleAdd.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class UserRoleAddTest extends BoltUnitTest
{
    public function testAdd()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app);
        $command = new UserRoleAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => 'admin']);
        $result = $tester->getDisplay();
        $this->assertEquals("User 'test' now has role 'admin'.", trim($result));
    }

    public function testRoleExists()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, true);
        $command = new UserRoleAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => 'admin']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/already has role/', trim($result));
    }

    public function testRoleFails()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, false, false);
        $command = new UserRoleAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => 'admin']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Could not add role/', trim($result));
    }

    protected function getUserMock($app, $hasRole = false, $addRole = true)
    {
        $users = $this->getMock('Bolt\Users', ['hasRole', 'addRole'], [$app]);
        $users->expects($this->any())
            ->method('hasRole')
            ->will($this->returnValue($hasRole));

        $users->expects($this->any())
            ->method('addRole')
            ->will($this->returnValue($addRole));

        return $users;
    }
}
