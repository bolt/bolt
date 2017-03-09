<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;
use Twig_Filter as TwigFilter;
use Twig_Function as TwigFunction;

/**
 * HTML functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class HtmlExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];
        $env  = ['needs_environment' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('markdown', [Runtime\HtmlRuntime::class, 'markdown'], $safe),
            new TwigFunction('menu',     [Runtime\HtmlRuntime::class, 'menu'], $env + $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $safe = ['is_safe' => ['html']];

        return [
            // @codingStandardsIgnoreStart
            new TwigFilter('editable', [Runtime\HtmlRuntime::class, 'editable'], $safe),
            new TwigFilter('markdown', [Runtime\HtmlRuntime::class, 'markdown'], $safe),
            new TwigFilter('shy',      [Runtime\HtmlRuntime::class, 'shy'], $safe),
            new TwigFilter('tt',       [Runtime\HtmlRuntime::class, 'decorateTT'], $safe),
            new TwigFilter('twig',     [Runtime\HtmlRuntime::class, 'twig'], ['needs_environment' => true] + $safe),
            // @codingStandardsIgnoreEnd
        ];
    }
}
