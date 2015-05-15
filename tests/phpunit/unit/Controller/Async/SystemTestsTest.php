<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Async/SystemTests.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class SystemTestsTest extends ControllerUnitTest
{
    public function testEmailNotification()
    {
        $this->getService('users')->setCurrentUser( $this->getService('users')->getUser('admin'));
        $this->setRequest(Request::create('/async/email/test/admin'));

        $response = $this->controller()->actionEmailNotification($this->getRequest(), 'test');

        $this->assertTrue($response instanceof Response);
        $this->assertSame('["Done"]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return \Bolt\Controller\Async\SystemTests
     */
    protected function controller()
    {
        return $this->getService('controller.async.system_tests');
    }
}
