<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Content record functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordExtension extends Extension
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
            new \Twig_SimpleFunction('current',            [Runtime\RecordRuntime::class, 'current']),
            new \Twig_SimpleFunction('excerpt',            [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new \Twig_SimpleFunction('fields',             [Runtime\RecordRuntime::class, 'fields'], $env + $safe + $deprecated + ['alternative' => 'block(\'sub_fields\')']),
            new \Twig_SimpleFunction('listtemplates',      [Runtime\RecordRuntime::class, 'listTemplates']),
            new \Twig_SimpleFunction('pager',              [Runtime\RecordRuntime::class, 'pager'], $env + $safe),
            new \Twig_SimpleFunction('trimtext',           [Runtime\RecordRuntime::class, 'excerpt'], $safe + $deprecated + ['alternative' => 'excerpt']),
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
            new \Twig_SimpleFilter('current',        [Runtime\RecordRuntime::class, 'current']),
            new \Twig_SimpleFilter('excerpt',        [Runtime\RecordRuntime::class, 'excerpt'], $safe),
            new \Twig_SimpleFilter('selectfield',    [Runtime\RecordRuntime::class, 'selectField']),
            new \Twig_SimpleFilter('trimtext',       [Runtime\RecordRuntime::class, 'excerpt'], $safe + $deprecated + ['alternative' => 'excerpt']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
