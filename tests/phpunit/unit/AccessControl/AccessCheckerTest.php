<?php
namespace Bolt\Tests;

use Bolt\AccessControl\AccessChecker;
use Bolt\AccessControl\Token\Token;
use Bolt\Storage\Entity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for AccessControl\AccessChecker
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessCheckerTest extends BoltUnitTest
{
    public function tearDown()
    {
        $this->resetDb();
    }

    public function testLoadAccessControl()
    {
        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);
    }

    public function testIsValidSessionNoCookie()
    {
        $accessControl = $this->getAccessControl();
        $this->assertInstanceOf('Bolt\AccessControl\AccessChecker', $accessControl);

        $response = $accessControl->isValidSession(null);
        $this->assertFalse($response);
    }

    /**
     * @return \Bolt\AccessControl\AccessChecker
     */
    protected function getAccessControl()
    {
        $request = Request::createFromGlobals();
        $request->server->set('HTTP_USER_AGENT', 'Bolt PHPUnit tests');

        $app = $this->getApp();
        $app['access_control']->setRequest($request);

        return $app['access_control'];
    }
}
