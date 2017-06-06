<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Widget functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WidgetExtension extends AbstractExtension
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

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }
}
