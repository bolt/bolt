<?php

namespace Bolt\Tests\Asset\Widget;

use Bolt\Asset\Widget\Widget;
use Bolt\Tests\BoltUnitTest;

/**
 * Bolt\Asset\Widget\Widget tests.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetTest extends BoltUnitTest
{
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

        $this->assertSame(42, $widget->getCacheDuration());
        $this->assertSame([$this, 'widgetCallback'], $widget->getCallback());
        $this->assertSame(['first' => 'clippy', 'second' => 'koala'], $widget->getCallbackArguments());
        $this->assertSame(['widget-bourgeoisie'], $widget->getClass());
        $this->assertSame('Some test content', $widget->getContent());
        $this->assertTrue($widget->isDeferred());
        $this->assertSame('somewhere', $widget->getLocation());
        $this->assertSame('after', $widget->getPostfix());
        $this->assertSame('before', $widget->getPrefix());
        $this->assertSame(0, $widget->getPriority());
        $this->assertSame('frontend', $widget->getZone());
    }

    public function testWidgetCasts()
    {
        $widget = new Widget();

        $widget
            ->setDefer(1)
            ->setPriority(null)
        ;

        $this->assertTrue($widget->isDeferred());
        $this->assertInternalType('boolean', $widget->isDeferred());
        $this->assertSame(0, $widget->getPriority());
        $this->assertInternalType('integer', $widget->getPriority());
    }

    public function testWidgetArrayAccess()
    {
        $widget = new Widget();

        $this->assertTrue(isset($widget['zone']));
        $this->assertFalse(isset($widget['koala']));

        $widget['zone'] = 'koala';
        $this->assertSame('koala', $widget['zone']);

        unset($widget['zone']);
        $this->assertNull($widget['zone']);
    }

    public function testWidgetCastStringFromContent()
    {
        $widget = new Widget();

        $widget->setContent('Some test content');

        $this->assertSame('Some test content', (string) $widget);
    }

    public function testWidgetCastStringFromCallback()
    {
        $widget = new Widget();

        $widget
            ->setCallback([$this, 'widgetCallback'])
            ->setCallbackArguments(['first' => 'Clippy', 'second' => 'Koala'])
            ->setContent('Some test content')
        ;

        $this->assertSame('Clippy gives gum leaves to the Koala', (string) $widget);
    }

    public function widgetCallback($second, $first)
    {
        return sprintf('%s gives gum leaves to the %s', $first, $second);
    }
}
