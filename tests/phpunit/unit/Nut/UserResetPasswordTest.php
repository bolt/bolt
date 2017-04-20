<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\UserResetPassword;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;
use PasswordLib\PasswordLib;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/UserResetPassword.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserResetPasswordTest extends BoltUnitTest
{
    public function testRun()
    {
        $this->resetDb();
        $app = $this->getApp();
        /** @var Repository\UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);
        $user = new Entity\Users([
            'username'    => 'koala',
            'password'    => 'GumL3@ve$',
            'email'       => 'koala@drop.bear.com.au',
            'displayname' => 'Drop Bear',
            'roles'       => ['root'],
        ]);
        $repo->save($user);

        $command = new UserResetPassword($app);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'koala', '--no-interaction' => true]);
        $result = $tester->getDisplay();
        $this->assertRegExp('#Resetting password for user #', $result);

        // Test that the saved value matches the hash
        $repo = $app['storage']->getRepository(Entity\Users::class);
        $userEntity = $repo->getUser('koala');
        $userAuth = $repo->getUserAuthData($userEntity->getId());
        $crypt = new PasswordLib();

        // Check the old password isn't valid
        $auth = $crypt->verifyPasswordHash('GumL3@ve$', $userAuth->getPassword());
        $this->assertFalse($auth);

        // Check the new password is valid
        preg_match('/New password for koala is .*/', $result, $matches);
        $bits = explode(' ', trim($matches[0]));
        $auth = $crypt->verifyPasswordHash($bits[5], $userAuth->getPassword());
        $this->assertTrue($auth);
    }
}
