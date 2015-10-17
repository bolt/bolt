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
            ->setType('frontend')
        ;

        $this->assertSame(42, $widget->getCacheDuration());
        $this->assertSame([$this, 'widgetCallback'], $widget->getCallback());
        $this->assertSame(['first' => 'clippy', 'second' => 'koala'], $widget->getCallbackArguments());
        $this->assertSame('bourgeoisie', $widget->getClass());
        $this->assertSame('Some test content', $widget->getContent());
        $this->assertTrue($widget->getDefer());
        $this->assertSame('somewhere', $widget->getLocation());
        $this->assertSame('after', $widget->getPostfix());
        $this->assertSame('before', $widget->getPrefix());
        $this->assertSame(0, $widget->getPriority());
        $this->assertSame('frontend', $widget->getType());
    }

    public function widgetCallback($second, $first)
    {
        return sprintf('%s gives gum leaves to the %s', $first, $second);
    }
}
