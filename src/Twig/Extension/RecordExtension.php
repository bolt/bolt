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
            new TwigFunction('contenttype',   [Runtime\RecordRuntime::class, 'contentType'], $safe),
            new TwigFunction('current',       [Runtime\RecordRuntime::class, 'current']),
            new TwigFunction('excerpt',       [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new TwigFunction('fields',        [Runtime\RecordRuntime::class, 'fields'], $env + $safe + $deprecated + ['alternative' => 'block(\'sub_fields\')']),
            new TwigFunction('listtemplates', [Runtime\RecordRuntime::class, 'listTemplates']),
            new TwigFunction('pager',         [Runtime\RecordRuntime::class, 'pager'], $env + $safe),
            new TwigFunction('related',       [Runtime\RecordRuntime::class, 'related'], $safe),
            new TwigFunction('taxonomy',      [Runtime\RecordRuntime::class, 'taxonomy'], $safe),
            new TwigFunction('trimtext',      [Runtime\RecordRuntime::class, 'excerpt'], $safe + $deprecated + ['alternative' => 'excerpt']),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $safe = ['is_safe' => ['html']];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFilter('contenttype', [Runtime\RecordRuntime::class, 'contentType'], $safe),
            new TwigFilter('current',     [Runtime\RecordRuntime::class, 'current']),
            new TwigFilter('excerpt',     [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new TwigFilter('selectfield', [Runtime\RecordRuntime::class, 'selectField']),
            new TwigFilter('related',     [Runtime\RecordRuntime::class, 'related'], $safe),
            new TwigFilter('taxonomy',    [Runtime\RecordRuntime::class, 'taxonomy'], $safe),
            new TwigFilter('trimtext',    [Runtime\RecordRuntime::class, 'excerpt'], $safe + $deprecated + ['alternative' => 'excerpt']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
