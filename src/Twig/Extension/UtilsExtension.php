<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;
use Twig_Function as TwigFunction;

/**
 * General-purpose utility functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UtilsExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('firebug',     [Runtime\UtilsRuntime::class, 'printFirebug']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
