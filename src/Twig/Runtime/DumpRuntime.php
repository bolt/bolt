<?php

namespace Bolt\Twig\Runtime;

use Bolt\Users;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Twig Bridge's DumpExtension's runtime logic with custom enabled check.
 * Also, backtrace function.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumpRuntime
{
    /** @var ClonerInterface */
    private $cloner;
    /** @var HtmlDumper */
    private $dumper;

    /** @var Users */
    private $users;
    /** @var bool */
    private $debugShowLoggedoff;

    /**
     * Constructor.
     *
     * @param ClonerInterface $cloner
     * @param HtmlDumper      $dumper
     * @param Users           $users
     * @param boolean         $debugShowLoggedoff
     */
    public function __construct(ClonerInterface $cloner, HtmlDumper $dumper, Users $users, $debugShowLoggedoff)
    {
        $this->cloner = $cloner;
        $this->dumper = $dumper;
        $this->users = $users;
        $this->debugShowLoggedoff = $debugShowLoggedoff;
    }

    /**
     * @param mixed $context
     */
    public function dump(\Twig_Environment $env, $context)
    {
        if (!$this->isEnabled($env)) {
            return null;
        }

        if (func_num_args() === 2) {
            $vars = [];
            foreach ($context as $key => $value) {
                if (!$value instanceof \Twig_Template) {
                    $vars[$key] = $value;
                }
            }

            $vars = [$vars];
        } else {
            $vars = func_get_args();
            unset($vars[0], $vars[1]);
        }

        $output = fopen('php://memory', 'r+b');
        $this->dumper->setCharset($env->getCharset());

        foreach ($vars as $value) {
            $this->dumper->dump($this->cloner->cloneVar($value), $output);
        }

        return stream_get_contents($output, -1, 0);
    }

    /**
     * Output pretty-printed backtrace.
     *
     * @param \Twig_Environment $env
     * @param array             $context
     * @param int               $depth
     *
     * @return string|null
     */
    public function dumpBacktrace(\Twig_Environment $env, $context, $depth)
    {
        if (!$this->isEnabled($env)) {
            return null;
        }

        return $this->dump($env, $context, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $depth));
    }

    /**
     * @param \Twig_Environment $env
     *
     * @return bool
     */
    protected function isEnabled(\Twig_Environment $env)
    {
        return $env->isDebug() && ($this->debugShowLoggedoff || $this->users->getCurrentUser() !== null);
    }
}
