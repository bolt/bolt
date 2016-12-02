<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Array functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ArrayExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('unique', [Runtime\ArrayRuntime::class, 'unique'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFilter('order',   [Runtime\ArrayRuntime::class, 'order']),
            new \Twig_SimpleFilter('shuffle', [Runtime\ArrayRuntime::class, 'shuffle']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
