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
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $count = $handler->countWidgets('gum-tree', 'frontend');
        $this->assertSame(1, $count);
    }

    public function testCountWidgetsNoLocationDefault()
    {
        $app = $this->getStrictVariablesApp(false);
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $count = $handler->countWidgets();
        $this->assertSame(0, $count);
    }

    public function testCountWidgetsNoLocationStrict()
    {
        $app = $this->getStrictVariablesApp(true);
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $this->setExpectedException('InvalidArgumentException', 'countwidgets() requires a location, none given');
        $app['asset.queue.widget']->add($widget);
        $handler->countWidgets();
    }

    public function testGetWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $result = $handler->getWidgets('gum-tree', 'frontend');
        $this->assertCount(1, $result);

        $this->assertInstanceOf('Bolt\Asset\Widget\Widget', reset($result));
    }

    public function testHasWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);

        $this->assertFalse($handler->hasWidgets('gum-tree', 'frontend'));

        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $this->assertTrue($handler->hasWidgets('gum-tree', 'frontend'));
    }

    public function testHasWidgetsNoLocationDefault()
    {
        $app = $this->getStrictVariablesApp(false);
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $this->assertFalse($handler->hasWidgets());
    }

    public function testHasWidgetsNoLocationStrict()
    {
        $app = $this->getStrictVariablesApp(true);
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $this->setExpectedException('InvalidArgumentException', 'haswidgets() requires a location, none given');
        $app['asset.queue.widget']->add($widget);
        $handler->hasWidgets();
    }

    public function testWidget()
    {
        $app = $this->getApp();
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $result = $handler->widgets('gum-tree', 'frontend');
        $this->assertRegExp('#<div class="widgetholder widgetholder-gum-tree">#', (string) $result);
        $this->assertRegExp('#<blink>Drop Bear Warning!</blink>#', (string) $result);
    }

    public function testWidgetNoLocationDefault()
    {
        $app = $this->getStrictVariablesApp(false);
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $result = $handler->widgets();
        $this->assertNull($result);
    }

    public function testWidgetNoLocationStrict()
    {
        $app = $this->getStrictVariablesApp(true);
        $handler = new WidgetHandler($app);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $this->setExpectedException('InvalidArgumentException', 'widgets() requires a location, none given');
        $app['asset.queue.widget']->add($widget);
        $handler->widgets();
    }

    /**
     * @param bool $strict
     *
     * @return \Bolt\Application
     */
    protected function getStrictVariablesApp($strict)
    {
        $app = $this->getApp(false);
        $app['config']->set('general/strict_variables', $strict);
        $app->boot();

        return $app;
    }
}
