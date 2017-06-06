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
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('getuser',   [Runtime\UserRuntime::class, 'getUser']),
            new TwigFunction('getuserid', [Runtime\UserRuntime::class, 'getUserId']),
            new TwigFunction('isallowed', [Runtime\UserRuntime::class, 'isAllowed']),
            new TwigFunction('token',     [Runtime\UserRuntime::class, 'token'], $deprecated + ['alternative' => 'csrf_token']),
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

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [];
    }
}
