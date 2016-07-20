<?php
namespace Bolt\Tests\Events;

use Bolt\Events\StorageEvent;
use Bolt\Legacy\Content;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Events/StorageEvent.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StorageEventTest extends BoltFunctionalTestCase
{
    public function testSetup()
    {
        $app = $this->getApp();
        $content = new Content($app);

        $event = new StorageEvent($content);
        $this->assertEquals(null, $event->isCreate());
        $this->assertEquals($content, $event->getContent());
        $this->assertEquals(null, $event->getId());
        $this->assertEquals(null, $event->getContentType());
    }

    public function testSetupWithRecord()
    {
        $app = $this->getApp();
        $content = new Content($app, 'pages');
        $content->setValue('id', 5);
        $event = new StorageEvent($content);
        $this->assertEquals(5, $event->getId());
        $this->assertEquals('pages', $event->getContentType());
    }

    public function testIsCreate()
    {
        $app = $this->getApp();
        $content = new Content($app, 'pages');
        $content->setValue('id', 5);

        $event = new StorageEvent($content);

        $event->setArgument('create', true);
        $this->assertTrue($event->isCreate());

        $event->setArgument('create', false);
        $this->assertFalse($event->isCreate());
    }
}
