<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Admin (back-end) functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('__',                 [Runtime\AdminRuntime::class, 'trans'], $safe),
            new \Twig_SimpleFunction('buid',               [Runtime\AdminRuntime::class, 'buid'], $safe),
            new \Twig_SimpleFunction('data',               [Runtime\AdminRuntime::class, 'addData']),
            new \Twig_SimpleFunction('hattr',              [Runtime\AdminRuntime::class, 'hattr'], $safe),
            new \Twig_SimpleFunction('hclass',             [Runtime\AdminRuntime::class, 'hclass'], $safe),
            new \Twig_SimpleFunction('ischangelogenabled', [Runtime\AdminRuntime::class, 'isChangelogEnabled'], $deprecated),
            new \Twig_SimpleFunction('randomquote',        [Runtime\AdminRuntime::class, 'randomQuote'], $safe),
            new \Twig_SimpleFunction('stack',              [Runtime\AdminRuntime::class, 'stack']),
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
            new \Twig_SimpleFilter('__',       [Runtime\AdminRuntime::class, 'trans']),
            new \Twig_SimpleFilter('loglevel', [Runtime\AdminRuntime::class, 'logLevel']),
            new \Twig_SimpleFilter('ymllink',  [Runtime\AdminRuntime::class, 'ymllink'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new \Twig_SimpleTest('stackable', [Runtime\AdminRuntime::class, 'testStackable']),
        ];
    }
}
