<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

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
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('link',           [Runtime\HtmlRuntime::class, 'link'], $safe + $deprecated),
            new \Twig_SimpleFunction('markdown',       [Runtime\HtmlRuntime::class, 'markdown'], $safe),
            new \Twig_SimpleFunction('menu',           [Runtime\HtmlRuntime::class, 'menu'], $env + $safe),
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
            new \Twig_SimpleFilter('editable', [Runtime\HtmlRuntime::class, 'editable'], $safe),
            new \Twig_SimpleFilter('markdown', [Runtime\HtmlRuntime::class, 'markdown'], $safe),
            new \Twig_SimpleFilter('shy',      [Runtime\HtmlRuntime::class, 'shy'], $safe),
            new \Twig_SimpleFilter('tt',       [Runtime\HtmlRuntime::class, 'decorateTT'], $safe),
            new \Twig_SimpleFilter('twig',     [Runtime\HtmlRuntime::class, 'twig'], ['needs_environment' => true] + $safe),
            // @codingStandardsIgnoreEnd
        ];
    }
}
