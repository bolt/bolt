<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Asset\Widget\Widget;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\WidgetRuntime;
use Silex\Application;

/**
 * Class to test Bolt\Twig\Runtime\WidgetRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetRuntimeTest extends BoltUnitTest
{
    public function testCountWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $count = $handler->countWidgets($app['twig'], 'gum-tree', 'frontend');
        $this->assertSame(1, $count);
    }

    public function testCountWidgetsNoLocationDefault()
    {
        $app = $this->getStrictVariablesApp(false);
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $count = $handler->countWidgets($app['twig']);
        $this->assertSame(0, $count);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage countwidgets() requires a location, none given
     */
    public function testCountWidgetsNoLocationStrict()
    {
        $app = $this->getStrictVariablesApp(true);
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $handler->countWidgets($app['twig']);
    }

    public function testGetWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $result = $handler->getWidgets();
        $this->assertCount(1, $result);

        $this->assertInstanceOf(Widget::class, reset($result));
    }

    public function testHasWidgets()
    {
        $app = $this->getApp();
        $handler = new WidgetRuntime($app['asset.queue.widget']);

        $this->assertFalse($handler->hasWidgets($app['twig'], 'gum-tree', 'frontend'));

        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $this->assertTrue($handler->hasWidgets($app['twig'], 'gum-tree', 'frontend'));
    }

    public function testHasWidgetsNoLocationDefault()
    {
        $app = $this->getStrictVariablesApp(false);
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $this->assertFalse($handler->hasWidgets($app['twig']));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage haswidgets() requires a location, none given
     */
    public function testHasWidgetsNoLocationStrict()
    {
        $app = $this->getStrictVariablesApp(true);
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $handler->hasWidgets($app['twig']);
    }

    public function testWidget()
    {
        $app = $this->getApp();
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);

        $result = $handler->widgets($app['twig'], 'gum-tree', 'frontend');
        $this->assertRegExp('#<div class="widgetholder widgetholder-gum-tree">#', (string) $result);
        $this->assertRegExp('#<blink>Drop Bear Warning!</blink>#', (string) $result);
    }

    public function testWidgetNoLocationDefault()
    {
        $app = $this->getStrictVariablesApp(false);
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $result = $handler->widgets($app['twig']);
        $this->assertNull($result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage widgets() requires a location, none given
     */
    public function testWidgetNoLocationStrict()
    {
        $app = $this->getStrictVariablesApp(true);
        $handler = new WidgetRuntime($app['asset.queue.widget']);
        $widget = (new Widget())
            ->setZone('frontend')
            ->setLocation('gum-tree')
            ->setContent('<blink>Drop Bear Warning!</blink>')
        ;

        $app['asset.queue.widget']->add($widget);
        $handler->widgets($app['twig']);
    }

    /**
     * @param bool $strict
     *
     * @return Application
     */
    protected function getStrictVariablesApp($strict)
    {
        $app = $this->getApp(false);
        $app['config']->set('general/strict_variables', $strict);
        $app->boot();

        return $app;
    }
}
