<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Content record functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordExtension extends AbstractExtension
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
            new TwigFunction('current',       [Runtime\RecordRuntime::class, 'current']),
            new TwigFunction('excerpt',       [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new TwigFunction('listtemplates', [Runtime\RecordRuntime::class, 'listTemplates']),
            new TwigFunction('pager',         [Runtime\RecordRuntime::class, 'pager'], $env + $safe),
            new TwigFunction('taxonomy',      [Runtime\RecordRuntime::class, 'taxonomy']),
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
            new TwigFilter('current',     [Runtime\RecordRuntime::class, 'current']),
            new TwigFilter('excerpt',     [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new TwigFilter('selectfield', [Runtime\RecordRuntime::class, 'selectField']),
            new TwigFilter('taxonomy',    [Runtime\RecordRuntime::class, 'taxonomy']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
