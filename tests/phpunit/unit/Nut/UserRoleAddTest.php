<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\UserRoleAdd;
use Bolt\Tests\BoltUnitTest;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
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
        $this->setService('users', $this->getUserMockWithReturns());
        $command = new UserRoleAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => ['admin']]);
        $result = $tester->getDisplay();
        $this->assertRegExp("/User 'test' now has role 'admin'/", $result);
    }

    public function testRoleExists()
    {
        $app = $this->getApp();
        $this->setService('users', $this->getUserMockWithReturns(true));
        $command = new UserRoleAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => ['admin']]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/already has role/', $result);
    }

    public function testRoleFails()
    {
        $app = $this->getApp();
        $this->setService('users', $this->getUserMockWithReturns(false, false));
        $command = new UserRoleAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'test', 'role' => ['admin']]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Could not add role/', $result);
    }

    protected function getUserMockWithReturns($hasRole = false, $addRole = true)
    {
        /** @var MockObject $users */
        $users = $this->getMockUsers(['hasRole', 'addRole']);
        $users->expects($this->any())
            ->method('hasRole')
            ->will($this->returnValue($hasRole));

        $users->expects($this->any())
            ->method('addRole')
            ->will($this->returnValue($addRole));

        return $users;
    }
}
