<?php

namespace Bolt\Tests\Asset\Widget;

use Bolt\Asset\Injector;
use Bolt\Asset\Widget\Queue;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;
use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @covers \Bolt\Asset\Widget\Queue
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class QueueTest extends TestCase
{
    protected $injector;
    protected $cache;
    protected $twig;

    public function testAdd()
    {
        $widget = Widget::create();
        $queue = $this->getQueue();
        $queue->add($widget);

        $queued = $queue->getQueue();

        self::assertSame($widget, reset($queued));
    }

    public function testClear()
    {
        $widget = Widget::create();
        $queue = $this->getQueue();
        $queue->add($widget);
        $queue->clear();

        $queued = $queue->getQueue();

        self::assertSame([], $queued);
    }

    public function testHas()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        self::assertTrue($queue->has('gum-tree-9'));
        self::assertFalse($queue->has('gum-tree-99'));
    }

    public function testCount()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        self::assertSame(1, $queue->count('gum-tree-9'));
        self::assertSame(0, $queue->count('gum-tree-99'));
    }

    public function testRenderEmpty()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        $this->twig
            ->expects($this->never())
            ->method('render')
        ;

        $queue->render('wrong-tree-9');
    }

    public function testRenderBasic()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        $this->twig
            ->expects($this->atLeastOnce())
            ->method('render')
        ;

        $queue->render('gum-tree-9');
    }

    public function testRenderUnCached()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
            ->setCacheDuration(42)
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        $this->twig
            ->expects($this->atLeastOnce())
            ->method('render')
        ;
        $this->cache
            ->expects($this->atLeastOnce())
            ->method('fetch')
        ;
        $this->cache
            ->expects($this->atLeastOnce())
            ->method('save')
        ;

        $queue->render('gum-tree-9');
    }

    public function testRenderCached()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
            ->setCacheDuration(42)
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        $this->twig
            ->expects($this->atLeastOnce())
            ->method('render')
        ;
        $this->cache
            ->expects($this->atLeastOnce())
            ->method('fetch')
            ->willReturn('DROP BEAR!')
        ;
        $this->cache
            ->expects($this->never())
            ->method('save')
        ;

        $queue->render('gum-tree-9');
    }

    public function testProcess()
    {
        $widget = Widget::create()
            ->setZone(Zone::FRONTEND)
            ->setLocation('gum-tree-9')
            ->setDefer(true)
        ;
        $queue = $this->getQueue();
        $queue->add($widget);

        $response = new Response();
        $request = new Request();
        Zone::set($request, 'backend');

        $this->injector
            ->expects($this->once())
            ->method('inject')
        ;

        $queue->process($request, $response);
        $queue->process($request, $response);
    }

    protected function getQueue()
    {
        return new Queue($this->injector, $this->cache, $this->twig);
    }

    protected function setUp()
    {
        $this->injector = $this->getMockBuilder(Injector::class)->getMock();
        $this->cache = $this->getMockBuilder(CacheProvider::class)->getMock();
        $this->twig = $this->getMockBuilder(Environment::class)->getMock();
    }

    protected function tearDown()
    {
        $this->injector = null;
        $this->cache = null;
        $this->twig = null;
    }
}
