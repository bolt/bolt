<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig_Extension as Extension;

/**
 * Image functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ImageExtension extends Extension
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
            new \Twig_SimpleFunction('fancybox',  [Runtime\ImageRuntime::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFunction('image',     [Runtime\ImageRuntime::class, 'image']),
            new \Twig_SimpleFunction('imageinfo', [Runtime\ImageRuntime::class, 'imageInfo']),
            new \Twig_SimpleFunction('popup',     [Runtime\ImageRuntime::class, 'popup'], $safe),
            new \Twig_SimpleFunction('showimage', [Runtime\ImageRuntime::class, 'showImage'], $safe),
            new \Twig_SimpleFunction('thumbnail', [Runtime\ImageRuntime::class, 'thumbnail']),
            // @codingStandardsIgnoreEnd
        ];
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
            new \Twig_SimpleFilter('fancybox',  [Runtime\ImageRuntime::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new \Twig_SimpleFilter('image',     [Runtime\ImageRuntime::class, 'image']),
            new \Twig_SimpleFilter('imageinfo', [Runtime\ImageRuntime::class, 'imageInfo']),
            new \Twig_SimpleFilter('popup',     [Runtime\ImageRuntime::class, 'popup'], $safe),
            new \Twig_SimpleFilter('showimage', [Runtime\ImageRuntime::class, 'showImage'], $safe),
            new \Twig_SimpleFilter('thumbnail', [Runtime\ImageRuntime::class, 'thumbnail']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
