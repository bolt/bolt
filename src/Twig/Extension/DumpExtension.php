<?php

namespace Bolt\Twig\Extension;

use Bolt\Twig\Runtime\DumpRuntime;
use Symfony\Bridge\Twig\TokenParser\DumpTokenParser;

/**
 * Modified version of Twig Bridge's DumpExtension to use runtime loading.
 * Also, backtrace function.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumpExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        $options = ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true];

        return [
            new \Twig_SimpleFunction('backtrace', [DumpRuntime::class, 'dumpBacktrace'], $options),
            new \Twig_SimpleFunction('dump', [DumpRuntime::class, 'dump'], $options),
            new \Twig_SimpleFunction('print', [DumpRuntime::class, 'dump'], $options + ['deprecated' => true, 'alternative' => 'dump']),
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
