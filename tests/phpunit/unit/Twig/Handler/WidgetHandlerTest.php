<?php

namespace Bolt\Tests\Twig;

use Bolt\Asset\Widget\Widget;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\WidgetHandler;

/**
 * Class to test Bolt\Twig\Handler\WidgetHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetHandlerTest extends BoltUnitTest
{
    public function testCountWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setType('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $count = $handler->countWidgets('frontend', 'gum-tree');
        $this->assertSame(1, $count);
    }

    public function testGetWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setType('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $result = $handler->getWidgets('frontend', 'gum-tree');
        $this->assertCount(1, $result);

        $this->assertInstanceOf('Bolt\Asset\Widget\Widget', reset($result));
    }

    public function testHasWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);

        $this->assertFalse($handler->hasWidgets('frontend', 'gum-tree'));

        $widget = (new Widget())
            ->setType('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $this->assertTrue($handler->hasWidgets('frontend', 'gum-tree'));
    }

    public function testWidget()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setType('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $result = $handler->widgets('frontend', 'gum-tree');
        $this->assertRegExp('#<div class="widgetholder widgetholder-gum-tree">#', $result);
        $this->assertRegExp('#<blink>Drop Bear Warning!</blink>#', $result);
    }
}
