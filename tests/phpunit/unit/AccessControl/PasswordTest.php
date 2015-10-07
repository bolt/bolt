<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Password;
use Hautelook\Phpass\PasswordHash;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;

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
            ->method('info')
            ->with($this->equalTo("Password for user 'admin' was reset via Nut."));
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
            ->method('error')
            ->with($this->equalTo('Somebody tried to reset a password with an invalid token.'));
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
            ->method('error')
            ->with($this->equalTo('Somebody tried to reset a password with an invalid token.'));
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

        $this->assertFalse($result);
    }

    public function testResetPasswordConfirmInvalidToken()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Somebody tried to reset a password with an invalid token.'));
        $app['logger.system'] = $logger;

        $shadowToken = $app['randomgenerator']->generateString(32);

        $userEntity = $repo->getUser('admin');
        $userEntity->setShadowpassword('hash-my-password');
        $userEntity->setShadowtoken('this should not work');
        $userEntity->setShadowvalidity(Carbon::create()->addHours(2));
        $repo->save($userEntity);

        $password = new Password($app);
        $result = $password->resetPasswordConfirm($shadowToken, '8.8.8.8');

        $this->assertFalse($result);
    }

    public function testResetPasswordRequestInvalidUser()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['info']);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->equalTo("A password reset link has been sent to 'sneakykoala'."));
        $app['logger.flash'] = $logger;

        $password = new Password($app);
        $result = $password->resetPasswordRequest('sneakykoala', '8.8.8.8');

        $this->assertFalse($result);
    }

    public function testResetPasswordRequestNoMailOptions()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('general/mailoptions', null);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo("The email configuration setting 'mailoptions' hasn't been set. Bolt may be unable to send password reset."));
        $app['logger.flash'] = $logger;

        $mailer = $this->getMock('\Swift_Mailer', array('send'), array($app['swiftmailer.transport']));
        $mailer->expects($this->atLeastOnce())
            ->method('send')
            ->will($this->returnValue(true));
        $app['mailer'] = $mailer;

        $password = new Password($app);
        $result = $password->resetPasswordRequest('admin', '8.8.8.8');

        $this->assertTrue($result);
    }

    public function testResetPasswordRequestSendSuccess()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('general/mailoptions', ['transport' => 'smtp', 'spool' => true,'host' => 'localhost', 'port' => '25']);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->never())
            ->method('error')
            ->with($this->equalTo("A password reset link has been sent to 'sneakykoala'."));
        $app['logger.flash'] = $logger;

        $mailer = $this->getMock('\Swift_Mailer', array('send'), array($app['swiftmailer.transport']));
        $mailer->expects($this->atLeastOnce())
            ->method('send')
            ->will($this->returnValue(true));
        $app['mailer'] = $mailer;

        $password = new Password($app);
        $result = $password->resetPasswordRequest('admin', '8.8.8.8');

        $this->assertTrue($result);
    }

    public function testResetPasswordRequestSendFailure()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('general/mailoptions', ['transport' => 'smtp', 'spool' => true,'host' => 'localhost', 'port' => '25']);

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo("Failed to send password request. Please check the email settings."));
        $app['logger.flash'] = $logger;

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo("Failed to send password request sent to 'Admin'."));
        $app['logger.system'] = $logger;

        $mailer = $this->getMock('\Swift_Mailer', array('send'), array($app['swiftmailer.transport']));
        $mailer->expects($this->atLeastOnce())
            ->method('send')
            ->will($this->returnValue(false));
        $app['mailer'] = $mailer;

        $password = new Password($app);
        $result = $password->resetPasswordRequest('admin', '8.8.8.8');

        $this->assertTrue($result);
    }
}
