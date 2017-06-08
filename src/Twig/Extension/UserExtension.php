<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * User functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('getuser',   [Runtime\UserRuntime::class, 'getUser']),
            new TwigFunction('getuserid', [Runtime\UserRuntime::class, 'getUserId']),
            new TwigFunction('isallowed', [Runtime\UserRuntime::class, 'isAllowed']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
