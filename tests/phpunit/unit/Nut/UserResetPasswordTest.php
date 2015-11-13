<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\UserResetPassword;
use Bolt\Storage\Entity;
use Bolt\Tests\BoltUnitTest;
use PasswordLib\PasswordLib;
use Symfony\Component\Console\Helper\HelperSet;
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
        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Users');
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

        $helper = $this->getMock('\Symfony\Component\Console\Helper\QuestionHelper', ['ask']);
        $helper->expects($this->once())
            ->method('ask')
            ->will($this->returnValue(true));
        $set = new HelperSet(['question' => $helper]);
        $command->setHelperSet($set);

        $tester->execute(['username' => 'koala'], ['interactive' => false]);
        $result = $tester->getDisplay();
        $this->assertRegExp('#New password for koala is #', trim($result));
        $this->assertSame(38, strlen(trim($result)));

        // Test that the saved value matches the hash
        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Users');
        $userEntity = $repo->getUser('koala');
        $userAuth = $repo->getUserAuthData($userEntity->getId());
        $crypt = new PasswordLib();

        // Check the old password isn't valid
        $auth = $crypt->verifyPasswordHash('GumL3@ve$', $userAuth->getPassword());
        $this->assertFalse($auth);

        // Check the new password is valid
        $bits = explode(' ', trim($result));
        $auth = $crypt->verifyPasswordHash($bits[5], $userAuth->getPassword());
        $this->assertTrue($auth);
    }
}
