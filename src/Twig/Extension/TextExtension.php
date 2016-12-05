<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Text functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TextExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [];
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
            new \Twig_SimpleFilter('json_decode',    [Runtime\TextRuntime::class, 'jsonDecode']),
            new \Twig_SimpleFilter('localdate',      [Runtime\TextRuntime::class, 'localeDateTime'], $safe + $deprecated + ['alternative' => 'localedatetime']),
            new \Twig_SimpleFilter('localedatetime', [Runtime\TextRuntime::class, 'localeDateTime'], $safe),
            new \Twig_SimpleFilter('preg_replace',   [Runtime\TextRuntime::class, 'pregReplace']),
            new \Twig_SimpleFilter('safestring',     [Runtime\TextRuntime::class, 'safeString'], $safe),
            new \Twig_SimpleFilter('slug',           [Runtime\TextRuntime::class, 'slug']),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new \Twig_SimpleTest('json', [Runtime\TextRuntime::class, 'testJson']),
        ];
    }
}
