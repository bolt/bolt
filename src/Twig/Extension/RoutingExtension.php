<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Routing functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RoutingExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('canonical',      [Runtime\RoutingRuntime::class, 'canonical']),
            new TwigFunction('editlink',       [Runtime\RoutingRuntime::class, 'editlink']),
            new TwigFunction('htmllang',       [Runtime\RoutingRuntime::class, 'htmlLang']),
            new TwigFunction('ismobileclient', [Runtime\RoutingRuntime::class, 'isMobileClient']),
            new TwigFunction('redirect',       [Runtime\RoutingRuntime::class, 'redirect'], ['deprecated' => true]),
            new TwigFunction('request',        [Runtime\RoutingRuntime::class, 'request'], ['deprecated' => true]),
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
