<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Password;
use Hautelook\Phpass\PasswordHash;

/**
 * Test for AccessControl\Password
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PasswordTest extends BoltUnitTest
{
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
}
