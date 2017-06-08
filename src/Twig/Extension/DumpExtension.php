<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime\DumpRuntime;
use Symfony\Bridge\Twig\TokenParser\DumpTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Modified version of Twig Bridge's DumpExtension to use runtime loading.
 * Also, backtrace function.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumpExtension extends AbstractExtension
{
    public function getFunctions()
    {
        $options = ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('backtrace', [DumpRuntime::class, 'dumpBacktrace'], $options),
            new TwigFunction('dump',      [DumpRuntime::class, 'dump'], $options),
            new TwigFunction('print',     [DumpRuntime::class, 'dump'], $options + ['deprecated' => true, 'alternative' => 'dump']),
            // @codingStandardsIgnoreEnd
        ];
    }

    public function getTokenParsers()
    {
        return [new DumpTokenParser()];
    }

    public function getName()
    {
        return 'dump';
    }
}
