<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\UserAdd;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/UserAdd.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class UserAddTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app);
        $command = new UserAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'username'    => 'test',
                'displayname' => 'Test',
                'email'       => 'test@example.com',
                'password'    => 'test',
                'role'        => 'admin'
            ]
        );
        $result = $tester->getDisplay();
        $this->assertEquals('Successfully created user: test', trim($result));
    }

    public function testAvailability()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, false);
        $command = new UserAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'username'    => 'test',
                'displayname' => 'Test',
                'email'       => 'test@example.com',
                'password'    => 'test',
                'role'        => 'admin'
            ]
        );
        $result = $tester->getDisplay();
        $this->assertRegExp("/username test already exists/", trim($result));
        $this->assertRegExp("/email test@example\.com exists/", trim($result));
        $this->assertRegExp("/display name Test already exists/", trim($result));
    }

    public function testFailure()
    {
        $app = $this->getApp();
        $app['users'] = $this->getUserMock($app, true, false);
        $command = new UserAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'username'    => 'test',
                'displayname' => 'Test',
                'email'       => 'test@example.com',
                'password'    => 'test',
                'role'        => 'admin'
            ]
        );
        $result = $tester->getDisplay();
        $this->assertEquals("Error creating user: test", trim($result));
    }

    protected function getUserMock($app, $availability = true, $save = true)
    {
        $users = $this->getMock('Bolt\Users', ['getUsers', 'checkAvailability', 'saveUser'], [$app]);
        $users->expects($this->any())
            ->method('getUsers')
            ->will($this->returnValue([]));

        $users->expects($this->any())
            ->method('checkAvailability')
            ->will($this->returnValue($availability));

        $users->expects($this->any())
            ->method('saveUser')
            ->will($this->returnValue($save));

        return $users;
    }
}
