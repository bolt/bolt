<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;
use Twig_Function as TwigFunction;

/**
 * User functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('getuser',   [Runtime\UserRuntime::class, 'getUser']),
            new TwigFunction('getuserid', [Runtime\UserRuntime::class, 'getUserId']),
            new TwigFunction('isallowed', [Runtime\UserRuntime::class, 'isAllowed']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
