<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\UserAdd;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository\UsersRepository;
use Bolt\Tests\BoltUnitTest;
use PasswordLib\PasswordLib;
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
        $this->resetDb();
        $app = $this->getApp();
        $command = new UserAdd($app);
        $command->setApplication($app['nut']);
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
        $this->assertRegExp('/Successfully created user: test/', $result);

        // Test that the saved value matches the hash
        /** @var UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);
        $userEntity = $repo->getUser('test');
        $userAuth = $repo->getUserAuthData($userEntity->getId());
        $crypt = new PasswordLib();
        $auth = $crypt->verifyPasswordHash('testPass', $userAuth->getPassword());
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
        $this->assertRegExp('#Error creating user:#', $result);
        $this->assertRegExp('#username is already in use#', $result);
        $this->assertRegExp('#displayname is already in use#', $result);
        $this->assertRegExp('#email address is already in use#', $result);
    }
}
