<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Password;
use Hautelook\Phpass\PasswordHash;
use Carbon\Carbon;

/**
 * Test for AccessControl\Password
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PasswordTest extends BoltUnitTest
{
    public function tearDown()
    {
        $this->resetDb();
    }

    public function testSetRandomPassword()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);

        $logger = $this->getMock('\Monolog\Logger', ['info'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('info');
        $app['logger.system'] = $logger;

        $password = new Password($app);
        $newPass = $password->setRandomPassword('admin');

        $userEntity = $repo->getUser('admin');

        $hasher = new PasswordHash($app['access_control.hash.strength'], true);
        $compare = $hasher->CheckPassword($newPass, $userEntity->getPassword());

        $this->assertTrue($compare);
        $this->assertEmpty($userEntity->getShadowpassword());
        $this->assertEmpty($userEntity->getShadowtoken());
        $this->assertNull($userEntity->getShadowvalidity());
    }

    public function testResetPasswordConfirmValidUser()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);

        $shadowToken = $app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', '8.8.8.8'));

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(1));
        $repo->save($userEntity);

        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '8.8.8.8');
        $userEntity = $repo->getUser('admin');

        $this->assertTrue($result);
        $this->assertEmpty($userEntity->getShadowpassword());
        $this->assertEmpty($userEntity->getShadowtoken());
        $this->assertNull($userEntity->getShadowvalidity());
    }

    public function testResetPasswordConfirmExpiredToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error');
        $app['logger.system'] = $logger;

        $shadowToken = $app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', '8.8.8.8'));

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(-1));
        $repo->save($userEntity);

        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '8.8.8.8');
        $userEntity = $repo->getUser('admin');

        $this->assertFalse($result);
    }

    public function testResetPasswordConfirmInvalidIp()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error');
        $app['logger.system'] = $logger;

        $shadowToken = $app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', '8.8.8.8'));

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(2));
        $repo->save($userEntity);

        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '1.1.1.1');
        $userEntity = $repo->getUser('admin');

        $this->assertFalse($result);
    }
}
