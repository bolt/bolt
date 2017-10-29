<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Image functionality Twig extension.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ImageExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];
        $env = ['needs_environment' => true];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('fancybox',  [Runtime\ImageRuntime::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new TwigFunction('image',     [Runtime\ImageRuntime::class, 'image'], $env),
            new TwigFunction('imageinfo', [Runtime\ImageRuntime::class, 'imageInfo']),
            new TwigFunction('popup',     [Runtime\ImageRuntime::class, 'popup'], $safe),
            new TwigFunction('showimage', [Runtime\ImageRuntime::class, 'showImage'], $safe),
            new TwigFunction('thumbnail', [Runtime\ImageRuntime::class, 'thumbnail']),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $safe = ['is_safe' => ['html']];
        $env = ['needs_environment' => true];
        $deprecated = ['deprecated' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFilter('fancybox',  [Runtime\ImageRuntime::class, 'popup'], $safe + $deprecated + ['alternative' => 'popup']),
            new TwigFilter('image',     [Runtime\ImageRuntime::class, 'image'], $env),
            new TwigFilter('imageinfo', [Runtime\ImageRuntime::class, 'imageInfo']),
            new TwigFilter('popup',     [Runtime\ImageRuntime::class, 'popup'], $safe),
            new TwigFilter('showimage', [Runtime\ImageRuntime::class, 'showImage'], $safe),
            new TwigFilter('thumbnail', [Runtime\ImageRuntime::class, 'thumbnail']),
            // @codingStandardsIgnoreEnd
        ];
    }
}
