<?php

namespace Bolt\Tests\Asset\Snippet;

use Bolt\Asset\Injector;
use Bolt\Asset\Snippet\Queue;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Bolt\Asset\Snippet\Queue
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class QueueTest extends TestCase
{
    protected $injector;
    protected $cache;

    public function testAdd()
    {
        $queue = $this->getQueue();
        $snippet = Snippet::create();

        $queue->add($snippet);

        self::assertSame([$snippet], $queue->getQueue());
    }

    public function testClear()
    {
        $queue = $this->getQueue();
        $snippet = Snippet::create();

        $queue->add($snippet);
        $queue->clear();

        self::assertSame([], $queue->getQueue());
    }

    public function testProcess()
    {
        $queue = $this->getQueue();
        $snippet = Snippet::create()
            ->setCallback('0055-call-me-now')
            ->setZone('backend')
            ->setLocation(Target::AFTER_HEAD_CSS)
        ;

        $queue->add($snippet);
        $response = new Response('<!-- Drop Bear alert -->');
        $request = new Request();
        Zone::set($request, 'backend');

        $this->injector
            ->expects($this->atLeastOnce())
            ->method('inject')
            ->with($snippet, Target::AFTER_HEAD_CSS, $response)
        ;

        $queue->process($request, $response);
    }

    protected function getQueue()
    {
        return new Queue($this->injector, $this->cache);
    }

    protected function setUp()
    {
        $this->injector = $this->getMockBuilder(Injector::class)->getMock();
        $this->cache = $this->getMockBuilder(CacheProvider::class)->getMock();
    }

    protected function tearDown()
    {
        $this->injector = null;
        $this->cache = null;
    }
}
