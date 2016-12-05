<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

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
            new \Twig_SimpleFunction('getuser',   [Runtime\UserRuntime::class, 'getUser']),
            new \Twig_SimpleFunction('getuserid', [Runtime\UserRuntime::class, 'getUserId']),
            new \Twig_SimpleFunction('isallowed', [Runtime\UserRuntime::class, 'isAllowed']),
            new \Twig_SimpleFunction('token',     [Runtime\UserRuntime::class, 'token'], $deprecated + ['alternative' => 'csrf_token']),
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
