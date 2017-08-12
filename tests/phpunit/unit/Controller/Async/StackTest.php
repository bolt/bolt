<?php

namespace Bolt\Tests\Controller\Async;

use Bolt\Common\Json;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Response\TemplateView;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use League\Flysystem\Memory\MemoryAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

        $request = Request::create('/async/stack/add', 'POST', [
            'filename' => 'foo.txt',
        ]);

        $response = $this->controller()->add($request);
        $this->assertTrue($response instanceof JsonResponse);
        $json = Json::parse($response->getContent());

        $this->assertNull($json['removed']);
        $this->assertContains('<div class="stackitem', $json['panel']);
        $this->assertContains('<li', $json['list']);
    }

    public function testShowStack()
    {
        $this->setSessionUser(new Entity\Users($this->getService('users')->getUser('admin')));

        $response = $this->controller()->show(Request::create('/async/stack/show'));

        $this->assertTrue($response instanceof TemplateView);
        $this->assertSame('@bolt/components/stack/panel.twig', $response->getTemplate());
    }

    /**
     * @return \Bolt\Controller\Async\Stack
     */
    protected function controller()
    {
        return $this->getService('controller.async.stack');
    }
}
