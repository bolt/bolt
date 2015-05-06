<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Async/Stack.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class StackTest extends ControllerUnitTest
{
    public function testAddStack()
    {
        $this->getService('users')->currentuser = $this->getService('users')->getUser('admin');
        $this->setRequest(Request::create('/async/addstack/foo'));

        $response = $this->controller()->actionAddStack($this->getRequest());

        $this->assertTrue($response);
    }

    public function testShowStack()
    {
        $this->getService('users')->currentuser = $this->getService('users')->getUser('admin');
        $this->setRequest(Request::create('/async/showstack'));

        $response = $this->controller()->actionShowStack($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-stack.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return \Bolt\Controller\Async\General
     */
    protected function controller()
    {
        return $this->getService('controller.async.stack');
    }
}
