<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
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
        $this->setSessionUser(new Entity\Users($this->getService('users')->getUser('admin')));
        $this->setRequest(Request::create('/async/stack/add/foo'));

        $response = $this->controller()->addStack('foo');

        $this->assertTrue($response);
    }

    public function testShowStack()
    {
        $this->setSessionUser(new Entity\Users($this->getService('users')->getUser('admin')));
        $this->setRequest(Request::create('/async/stack/show'));

        $response = $this->controller()->showStack($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/components/panel-stack.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return \Bolt\Controller\Async\Stack
     */
    protected function controller()
    {
        return $this->getService('controller.async.stack');
    }
}
