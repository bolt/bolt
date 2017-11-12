<?php

namespace Bolt\Tests\Asset\File;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Queue;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Injector;
use Bolt\Asset\Target;
use Bolt\Config;
use Bolt\Controller\Zone;
use Bolt\Tests\Fixtures\FileAssetInterface\InvalidFileAsset;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Bolt\Asset\File\Queue
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class QueueTest extends TestCase
{
    protected $injector;
    protected $packages;
    protected $config;

    public function testAdd()
    {
        list($css, $js) = $this->getAssets();
        $queue = $this->getQueue();
        $queue->add($css);
        $queue->add($js);

        $queued = $queue->getQueue();

        self::assertSame($css, $queued['stylesheet']['https://koala.com.au/koala.css']);
        self::assertSame($js, $queued['javascript']['https://koala.com.au/dropbear.js']);
    }

    public function testAddMissingPackageName()
    {
        $this->markTestSkipped('Enable for Bolt v4');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File asset with path "dropbear" was added to the queue without an asset package specified.');

        $queue = $this->getQueue();
        $queue->add(new InvalidFileAsset('dropbear'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Requested asset type invalid is not valid
     */
    public function testAddInvalidAssetType()
    {
        // Enable for Bolt v4
        //$this->expectException(\InvalidArgumentException::class);
        //$this->expectExceptionMessage('Requested asset type invalid is not valid');

        $this->packages
            ->expects($this->any())
            ->method('getUrl')
            ->willReturn('')
        ;
        $queue = $this->getQueue();
        $queue->add(new InvalidFileAsset('koala', 'dropbear'));
    }

    public function testClear()
    {
        list($css, $js) = $this->getAssets();
        $queue = $this->getQueue();
        $queue->add($css);
        $queue->add($js);

        $queue->clear();
        $queued = $queue->getQueue();

        self::assertEmpty($queued['stylesheet']);
        self::assertEmpty($queued['javascript']);
    }

    public function testProcess()
    {
        list($css, $js) = $this->getAssets();
        $css->setZone('backend');
        $js->setZone('backend');
        $queue = $this->getQueue();
        $queue->add($css);
        $queue->add($js);

        $response = new Response();
        $request = new Request();
        Zone::set($request, 'backend');

        $this->injector
            ->expects($this->at(0))
            ->method('inject')
            ->with($js, Target::END_OF_HEAD, $response)
        ;
        $this->injector
            ->expects($this->at(1))
            ->method('inject')
            ->with($css, Target::END_OF_HEAD, $response)
        ;

        $queue->process($request, $response);
    }

    public function testProcessLateNoLocation()
    {
        list($css, $js) = $this->getAssets();
        $css->setZone('backend')->setLate(true);
        $js->setZone('backend')->setLate(true);
        $queue = $this->getQueue();
        $queue->add($css);
        $queue->add($js);

        $response = new Response();
        $request = new Request();
        Zone::set($request, 'backend');

        $this->injector
            ->expects($this->at(0))
            ->method('inject')
            ->with($js, Target::END_OF_BODY, $response)
        ;
        $this->injector
            ->expects($this->at(1))
            ->method('inject')
            ->with($css, Target::END_OF_BODY, $response)
        ;

        $queue->process($request, $response);
    }

    public function testProcessLateWithLocation()
    {
        list($css, $js) = $this->getAssets();
        $css->setZone('backend')->setLate(false)->setLocation(Target::AFTER_HEAD_CSS);
        $js->setZone('backend')->setLate(false)->setLocation(Target::AFTER_HEAD_JS);
        $queue = $this->getQueue();
        $queue->add($css);
        $queue->add($js);

        $response = new Response();
        $request = new Request();
        Zone::set($request, 'backend');

        $this->injector
            ->expects($this->at(0))
            ->method('inject')
            ->with($js, Target::AFTER_HEAD_JS, $response)
        ;
        $this->injector
            ->expects($this->at(1))
            ->method('inject')
            ->with($css, Target::AFTER_HEAD_CSS, $response)
        ;

        $queue->process($request, $response);
    }

    public function testProcessInvalidZone()
    {
        list($css, $js) = $this->getAssets();

        $css->setZone('backend');
        $js->setZone('backend');
        $queue = $this->getQueue();
        $queue->add($css);
        $queue->add($js);

        $this->injector
            ->expects($this->never())
            ->method('inject')
        ;

        $queue->process(new Request(), new Response());
    }

    protected function getAssets()
    {
        $this->packages
            ->expects($this->at(0))
            ->method('getUrl')
            ->willReturn('https://koala.com.au/koala.css')
        ;
        $this->packages
            ->expects($this->at(1))
            ->method('getUrl')
            ->willReturn('https://koala.com.au/dropbear.js')
        ;
        $css = Stylesheet::create('koala.css', 'gum-tree');
        $js = JavaScript::create('dropbear.js', 'danger');

        return [$css, $js];
    }

    protected function getQueue()
    {
        return new Queue($this->injector, $this->packages, $this->config);
    }

    protected function setUp()
    {
        $this->injector = $this->getMockBuilder(Injector::class)->getMock();
        $this->packages = $this->getMockBuilder(Packages::class)->getMock();
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        $this->injector = null;
        $this->packages = null;
        $this->config = null;
    }
}
