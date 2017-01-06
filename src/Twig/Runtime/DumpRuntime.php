<?php

namespace Bolt\Twig\Runtime;

use Bolt\Users;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Twig Bridge's DumpExtension's runtime logic with custom enabled check.
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
     * {@inheritdoc}
     */
    public function dump(\Twig_Environment $env, $context)
    {
        // Return if 'debug' is `false` in Twig, or there's no logged on user _and_ `debug_show_loggedoff` in
        // config.yml is `false`.
        if (!$env->isDebug() || (($this->users->getCurrentUser() === null) && !$this->debugShowLoggedoff)) {
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
}
