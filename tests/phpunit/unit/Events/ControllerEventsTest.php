<?php
namespace Bolt\Tests\Events;

use Bolt\Events\ControllerEvents;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test Bolt\Events\ControllerEvents
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerEventsTest extends BoltFunctionalTestCase
{
    public function testConstant()
    {
        $this->assertSame('controller.mount', ControllerEvents::MOUNT);
    }

    public function testSingletonConstructor()
    {
        $reflection = new \ReflectionClass('Bolt\Events\ControllerEvents');
        $method = $reflection->getMethod('__construct');

        $this->assertTrue($method->isConstructor());
        $this->assertTrue($method->isPrivate());
    }
}
