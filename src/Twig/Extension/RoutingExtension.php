<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Routing functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RoutingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // @codingStandardsIgnoreStart
            new \Twig_SimpleFunction('canonical',      [Runtime\RoutingRuntime::class, 'canonical']),
            new \Twig_SimpleFunction('htmllang',       [Runtime\RoutingRuntime::class, 'htmlLang']),
            new \Twig_SimpleFunction('ismobileclient', [Runtime\RoutingRuntime::class, 'isMobileClient']),
            new \Twig_SimpleFunction('redirect',       [Runtime\RoutingRuntime::class, 'redirect'], ['deprecated' => true]),
            new \Twig_SimpleFunction('request',        [Runtime\RoutingRuntime::class, 'request'], ['deprecated' => true]),
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
