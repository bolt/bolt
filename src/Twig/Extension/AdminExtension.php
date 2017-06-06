<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Admin (back-end) functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AdminExtension extends AbstractExtension
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
            new TwigFunction('__',                 [Runtime\AdminRuntime::class, 'trans'], $safe),
            new TwigFunction('buid',               [Runtime\AdminRuntime::class, 'buid'], $safe),
            new TwigFunction('data',               [Runtime\AdminRuntime::class, 'addData']),
            new TwigFunction('hattr',              [Runtime\AdminRuntime::class, 'hattr'], $safe),
            new TwigFunction('hclass',             [Runtime\AdminRuntime::class, 'hclass'], $safe),
            new TwigFunction('ischangelogenabled', [Runtime\AdminRuntime::class, 'isChangelogEnabled'], $deprecated),
            new TwigFunction('randomquote',        [Runtime\AdminRuntime::class, 'randomQuote'], $safe),
            new TwigFunction('stack',              [Runtime\AdminRuntime::class, 'stack']),
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
            new TwigFilter('__',       [Runtime\AdminRuntime::class, 'trans']),
            new TwigFilter('loglevel', [Runtime\AdminRuntime::class, 'logLevel']),
            new TwigFilter('ymllink',  [Runtime\AdminRuntime::class, 'ymllink'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('stackable', [Runtime\AdminRuntime::class, 'testStackable']),
        ];
    }
}
