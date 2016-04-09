<?php

namespace Bolt\Twig;

use Bolt\Users;
use Symfony\Bridge\Twig\Extension\DumpExtension as BaseDumpExtension;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * Extended to allow dumper to be passed in.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DumpExtension extends BaseDumpExtension
{
    /** @var ClonerInterface */
    private $cloner;
    /** @var DataDumperInterface */
    private $dumper;
    /** @var Users */
    private $users;

    private $debugShowLoggedoff;

    /**
     * Constructor.
     *
     * @param ClonerInterface     $cloner
     * @param DataDumperInterface $dumper
     * @param Users               $users
     * @param boolean             $debugShowLoggedoff
     */
    public function __construct(ClonerInterface $cloner, DataDumperInterface $dumper, Users $users, $debugShowLoggedoff)
    {
        parent::__construct($cloner);
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
        $prevOutput = $this->dumper->setOutput($output);

        foreach ($vars as $value) {
            $this->dumper->dump($this->cloner->cloneVar($value));
        }

        $this->dumper->setOutput($prevOutput);

        rewind($output);

        return stream_get_contents($output);
    }
}
