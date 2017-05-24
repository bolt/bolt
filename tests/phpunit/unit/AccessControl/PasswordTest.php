<?php

namespace Bolt\Tests\AccessControl;

use Bolt\AccessControl\Password;
use Bolt\Events\AccessControlEvent;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;
use Carbon\Carbon;
use PasswordLib\PasswordLib;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for AccessControl\Password.
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
        /** @var Repository\UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->equalTo("Password for user 'admin' was reset via Nut."));
        $this->setService('logger.system', $logger);

        $password = new Password($app);
        $newPass = $password->setRandomPassword('admin');

        /** @var Entity\Users $userEntity */
        $userEntity = $repo->getUser('admin');
        $userAuth = $repo->getUserAuthData($userEntity->getId());

        $crypt = new PasswordLib();
        $compare = $crypt->verifyPasswordHash($newPass, $userAuth->getPassword());

        $this->assertTrue($compare);
        $this->assertEmpty($userEntity->getShadowpassword());
        $this->assertEmpty($userEntity->getShadowtoken());
        $this->assertNull($userEntity->getShadowvalidity());
    }

    public function testResetPasswordConfirmValidUser()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        /** @var Repository\UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);

        $shadowToken = $app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', '8.8.8.8'));

        /** @var Entity\Users $userEntity */
        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(1));
        $repo->save($userEntity);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '8.8.8.8', $event);
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
        /** @var Repository\UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Somebody tried to reset a password with an invalid token.'));
        $this->setService('logger.system', $logger);

        $shadowToken = $app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', '8.8.8.8'));

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(-1));
        $repo->save($userEntity);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '8.8.8.8', $event);

        $this->assertFalse($result);
    }

    public function testResetPasswordConfirmInvalidIp()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        /** @var Repository\UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Somebody tried to reset a password with an invalid token.'));
        $this->setService('logger.system', $logger);

        $shadowToken = $app['randomgenerator']->generateString(32);
        $shadowTokenHash = md5($shadowToken . '-' . str_replace('.', '-', '8.8.8.8'));

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken($shadowTokenHash);
        $userEntity->setShadowvalidity(Carbon::create()->addHours(2));
        $repo->save($userEntity);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '1.1.1.1', $event);

        $this->assertFalse($result);
    }

    public function testResetPasswordConfirmInvalidToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        /** @var Repository\UsersRepository $repo */
        $repo = $app['storage']->getRepository(Entity\Users::class);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Somebody tried to reset a password with an invalid token.'));
        $this->setService('logger.system', $logger);

        $shadowToken = $app['randomgenerator']->generateString(32);

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken('this should not work');
        $userEntity->setShadowvalidity(Carbon::create()->addHours(2));
        $repo->save($userEntity);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '8.8.8.8', $event);

        $this->assertFalse($result);
    }

    public function testResetPasswordRequestInvalidUser()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->equalTo("A password reset link has been sent to 'sneakykoala'."));
        $this->setService('logger.flash', $logger);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordRequest('sneakykoala', '8.8.8.8', $event);

        $this->assertFalse($result);
    }

    public function testResetPasswordRequestNoMailOptions()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('general/mailoptions', null);

        $logger = $this->getMockFlashLogger();
        $logger->expects($this->atLeastOnce())
            ->method('danger')
            ->with($this->equalTo("The email configuration setting 'mailoptions' hasn't been set. Bolt may be unable to send password reset."));
        $this->setService('logger.flash', $logger);

        $mailer = $this->getMockSwiftMailer();
        $mailer->expects($this->atLeastOnce())
            ->method('send')
            ->will($this->returnValue(true));
        $this->setService('mailer', $mailer);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordRequest('admin', '8.8.8.8', $event);

        $this->assertTrue($result);
    }

    public function testResetPasswordRequestSendSuccess()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('general/mailoptions', ['transport' => 'smtp', 'spool' => true, 'host' => 'localhost', 'port' => '25']);

        $logger = $this->getMockFlashLogger();
        $logger->expects($this->never())
            ->method('error')
            ->with($this->equalTo("A password reset link has been sent to 'sneakykoala'."));
        $this->setService('logger.flash', $logger);

        $mailer = $this->getMockSwiftMailer();
        $mailer->expects($this->atLeastOnce())
            ->method('send')
            ->will($this->returnValue(true));
        $this->setService('mailer', $mailer);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordRequest('admin', '8.8.8.8', $event);

        $this->assertTrue($result);
    }

    public function testResetPasswordRequestSendFailure()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('general/mailoptions', ['transport' => 'smtp', 'spool' => true, 'host' => 'localhost', 'port' => '25']);

        $logger = $this->getMockFlashLogger();
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Failed to send password request. Please check the email settings.'));
        $this->setService('logger.flash', $logger);

        $logger = $this->getMockMonolog();
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo("Failed to send password request sent to 'Admin'."));
        $this->setService('logger.system', $logger);

        $mailer = $this->getMockSwiftMailer();
        $mailer->expects($this->atLeastOnce())
            ->method('send')
            ->will($this->returnValue(false));
        $this->setService('mailer', $mailer);

        $event = new AccessControlEvent(Request::createFromGlobals());
        $password = new Password($app);
        $result = $password->resetPasswordRequest('admin', '8.8.8.8', $event);

        $this->assertTrue($result);
    }
}
