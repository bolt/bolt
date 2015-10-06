<?php
namespace Bolt\Tests;

use Bolt\AccessControl\Login;
use Hautelook\Phpass\PasswordHash;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for AccessControl\LoginTest
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoginTest extends BoltUnitTest
{
    public function tearDown()
    {
        $this->resetDb();
    }

    public function testLoginNoCredentials()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Invalid login parameters.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);
        $request = new Request();

        $login->login($request);
    }

    public function testLoginInvalidUsername()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Your account is disabled. Sorry about that.'));
        $app['logger.flash'] = $logger;

        $login = new Login($app);
        $request = new Request();

        $login->login($request, 'koala', 'sneaky');
    }

    public function testLoginDisabledUsername()
    {
        $this->markTestSkipped('See issue #4237 Unable to disable users');

        $app = $this->getApp();
        $this->addDefaultUser($app);

        $logger = $this->getMock('\Monolog\Logger', ['error'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo("Attempt to login with disabled account by 'admin'"));
        $app['logger.system'] = $logger;

        $logger = $this->getMock('\Bolt\Logger\FlashLogger', ['error']);
        $logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->equalTo('Your account is disabled. Sorry about that.'));
        $app['logger.flash'] = $logger;

        $entityName = 'Bolt\Storage\Entity\Users';
        $repo = $app['storage']->getRepository($entityName);
        $userEntity = $repo->getUser('admin');
        $userEntity->setEnabled(false);
        $repo->save($userEntity);

        $login = new Login($app);
        $request = new Request();

        $login->login($request, 'admin', 'sneaky');
    }
}
