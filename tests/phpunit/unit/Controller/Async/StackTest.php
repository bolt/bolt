<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Response\BoltResponse;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use League\Flysystem\Memory\MemoryAdapter;
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
    protected function setUp()
    {
        parent::setUp();

        /** @var Manager $filesystem */
        $filesystem = $this->getService('filesystem');
        $filesystem->mountFilesystem('files', new Filesystem(new MemoryAdapter()));
        $filesystem->put('files://foo.txt', '');
    }

    public function testAddStack()
    {
        $this->setSessionUser(new Entity\Users($this->getService('users')->getUser('admin')));

        $response = $this->controller()->add('foo.txt');
        $this->assertTrue($response instanceof JsonResponse);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(-1, $json['indexToRemove']);
        $this->assertContains('<div class="stackitem', $json['newItem']);
    }

    public function testShowStack()
    {
        $this->setSessionUser(new Entity\Users($this->getService('users')->getUser('admin')));

        $response = $this->controller()->show(Request::create('/async/stack/show'));

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/components/stack/panel.twig', $response->getTemplateName());
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
