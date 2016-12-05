<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Widget functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('countwidgets', [Runtime\WidgetRuntime::class, 'countWidgets'], $safe),
            new \Twig_SimpleFunction('getwidgets',   [Runtime\WidgetRuntime::class, 'getWidgets'], $safe),
            new \Twig_SimpleFunction('haswidgets',   [Runtime\WidgetRuntime::class, 'hasWidgets'], $safe),
            new \Twig_SimpleFunction('widgets',      [Runtime\WidgetRuntime::class, 'widgets'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }
}
