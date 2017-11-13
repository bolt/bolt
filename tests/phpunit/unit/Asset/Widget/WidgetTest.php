<?php

namespace Bolt\Tests\Asset\Widget;

use Bolt\Asset\Widget\Widget;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Asset\Widget\Widget
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetTest extends TestCase
{
    public function testCreate()
    {
        self::assertInstanceOf(Widget::class, Widget::create());
    }

    public function testKey()
    {
        $widget = Widget::create();

        self::assertInstanceOf(Widget::class, $widget->setKey());
        self::assertNotEmpty($widget->getKey());
    }

    public function testClass()
    {
        $widget = Widget::create();

        self::assertInstanceOf(Widget::class, $widget->setClass(['koala', 'dropbear']));
        self::assertSame(['widget-koala', 'widget-dropbear'], $widget->getClass());
    }

    public function testWidgetBasicSetup()
    {
        $widget = new Widget();

        $widget
            ->setCacheDuration(42)
            ->setCallback([$this, 'widgetCallback'])
            ->setCallbackArguments(['first' => 'clippy', 'second' => 'koala'])
            ->setClass('bourgeoisie')
            ->setContent('Some test content')
            ->setDefer(true)
            ->setLocation('somewhere')
            ->setPostfix('after')
            ->setPrefix('before')
            ->setPriority(0)
            ->setZone('frontend')
        ;

        self::assertSame(42, $widget->getCacheDuration());
        self::assertSame([$this, 'widgetCallback'], $widget->getCallback());
        self::assertSame(['first' => 'clippy', 'second' => 'koala'], $widget->getCallbackArguments());
        self::assertSame(['widget-bourgeoisie'], $widget->getClass());
        self::assertSame('Some test content', $widget->getContent());
        self::assertTrue($widget->isDeferred());
        self::assertSame('somewhere', $widget->getLocation());
        self::assertSame('after', $widget->getPostfix());
        self::assertSame('before', $widget->getPrefix());
        self::assertSame(0, $widget->getPriority());
        self::assertSame('frontend', $widget->getZone());
    }

    public function testWidgetCasts()
    {
        $widget = new Widget();

        $widget
            ->setDefer(1)
            ->setPriority(0)
        ;

        self::assertTrue($widget->isDeferred());
        self::assertInternalType('boolean', $widget->isDeferred());
        self::assertSame(0, $widget->getPriority());
        self::assertInternalType('integer', $widget->getPriority());
    }

    public function testWidgetArrayAccess()
    {
        $widget = new Widget();

        self::assertTrue(isset($widget['zone']));
        self::assertFalse(isset($widget['koala']));

        $widget['zone'] = 'koala';
        self::assertSame('koala', $widget['zone']);

        unset($widget['zone']);
        self::assertNull($widget['zone']);
    }

    public function testWidgetCastStringFromContent()
    {
        $widget = new Widget();

        $widget->setContent('Some test content');

        self::assertSame('Some test content', (string) $widget);
    }

    public function testWidgetCastStringFromCallback()
    {
        $widget = new Widget();

        $widget
            ->setCallback([$this, 'widgetCallback'])
            ->setCallbackArguments(['first' => 'Clippy', 'second' => 'Koala'])
            ->setContent('Some test content')
        ;

        self::assertSame('Clippy gives gum leaves to the Koala', (string) $widget);
    }

    public function widgetCallback($second, $first)
    {
        return sprintf('%s gives gum leaves to the %s', $first, $second);
    }
}
