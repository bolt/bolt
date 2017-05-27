<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;
use Twig_Function as TwigFunction;

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
        $env = ['needs_environment' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('countwidgets', [Runtime\WidgetRuntime::class, 'countWidgets'], $safe + $env),
            new TwigFunction('getwidgets',   [Runtime\WidgetRuntime::class, 'getWidgets'], $safe),
            new TwigFunction('haswidgets',   [Runtime\WidgetRuntime::class, 'hasWidgets'], $safe + $env),
            new TwigFunction('widgets',      [Runtime\WidgetRuntime::class, 'widgets'], $safe + $env),
            // @codingStandardsIgnoreEnd
        ];
    }
}
