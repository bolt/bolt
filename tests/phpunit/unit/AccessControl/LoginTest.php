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
}
