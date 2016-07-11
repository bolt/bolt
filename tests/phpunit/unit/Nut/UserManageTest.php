<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\UserManage;
use Bolt\Storage\Repository\UsersRepository;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for Nut user:manage
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserManageTest extends BoltUnitTest
{
    public function testListing()
    {
        $app = $this->getApp();
        $this->resetDb();
        $this->addNewUser($app, 'koala', 'Kenny Koala', true);

        $command = new UserManage($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'login'  => 'koala',
                '--list' => null,
            ]
        );
        $result = $tester->getDisplay();
        $this->assertRegExp('#koala(\s*)\|(\s*)koala@example\.com(\s*)\|(\s*)Kenny Koala(\s*)\|(\s*)\d*(\s*)\|(\s*)true#', $result);
    }

    public function testEnable()
    {
        $app = $this->getApp();
        $this->resetDb();
        $this->addNewUser($app, 'koala', 'Kenny Koala', false);

        $command = new UserManage($app);
        $tester = new CommandTester($command);

        $tester->execute(
            [
                'login'  => 'koala',
                '--enable' => null,
            ]
        );
        $result = $tester->getDisplay();
        $this->assertSame('Enabled user: koala', trim($result));

        /** @var UsersRepository $repo */
        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Users');
        $userEntity = $repo->getUser('koala');
        $this->assertTrue($userEntity->getEnabled());
    }

    public function testDisable()
    {
        $app = $this->getApp();
        $this->resetDb();
        $this->addNewUser($app, 'koala', 'Kenny Koala', true);

        $command = new UserManage($app);
        $tester = new CommandTester($command, ['verbosity' => 256]);

        $tester->execute(
            [
                'login'  => 'koala',
                '--disable' => null,
            ]
        );

        $result = $tester->getDisplay();
        $this->assertSame('Disabled user: koala', trim($result));

        /** @var UsersRepository $repo */
        $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Users');
        $userEntity = $repo->getUser('koala');
        $this->assertFalse($userEntity->getEnabled());
    }
}
