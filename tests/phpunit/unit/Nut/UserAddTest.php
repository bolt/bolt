<?php

namespace Bolt\Tests\Nut;

use Bolt\AccessControl\PasswordHashManager;
use Bolt\Nut\UserAdd;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;
use Bolt\Storage\Entity;

/**
 * Class to test src/Nut/UserAdd.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class UserAddTest extends BoltUnitTest
{
    public function testRun()
    {
        $this->resetDb();
        $app = $this->getApp();
        $command = new UserAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'username'    => 'test',
                'displayname' => 'Test',
                'email'       => 'test@example.com',
                'password'    => 'testPass',
                'role'        => 'admin',
            ]
        );
        $result = $tester->getDisplay();
        $this->assertEquals('Successfully created user: test', trim($result));

        // Test that the saved value matches the hash
        $repo = $app['storage']->getRepository(Entity\Users::class);
        $userEntity = $repo->getUser('test');
        $userAuth = $repo->getUserAuthData($userEntity->getId());
        $crypt = new PasswordHashManager();
        $auth = $crypt->verifyHash('testPass', $userAuth->getPassword());
        $this->assertTrue($auth);
    }

    public function testAvailability()
    {
        $app = $this->getApp();
        $command = new UserAdd($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'username'    => 'test',
                'displayname' => 'Test',
                'email'       => 'test@example.com',
                'password'    => 'test',
                'role'        => 'admin',
            ]
        );
        $result = $tester->getDisplay();
        $this->assertRegExp('#Error creating user:#', trim($result));
        $this->assertRegExp("#    \* User name 'test' already exists#", trim($result));
        $this->assertRegExp("#    \* Email address 'test@example.com' already exists#", trim($result));
    }
}
