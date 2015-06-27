<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Async/SystemChecks.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class SystemChecksTest extends ControllerUnitTest
{
    public function testEmailNotification()
    {
        $this->getService('config')->set('general/mailoptions/transport', 'mail');
        $this->getService('config')->set('general/mailoptions/spool', false);

        $this->setRequest(Request::create('/async/check/email'));

        $response = $this->controller()->emailCheck($this->getRequest(), 'test');

        $this->assertTrue($response instanceof Response);
//        $this->assertSame('["Done"]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return \Bolt\Controller\Async\SystemChecks
     */
    protected function controller()
    {
        return $this->getService('controller.async.system_checks');
    }
}
