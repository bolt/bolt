<?php
namespace Bolt\Tests\Events;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Content;
use Bolt\Events\StorageEvent;

/**
 * Class to test src/Events/StorageEvent.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StorageEventTest extends BoltUnitTest
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
        $event = new StorageEvent(array('test', 5));
        $this->assertEquals(5, $event->getId());
        $this->assertEquals('test', $event->getContentType());
    }
    
   
}
