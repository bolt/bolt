<?php

namespace Bolt\Twig;

use Bolt\Users;
use Symfony\Bridge\Twig\Extension\DumpExtension as BaseDumpExtension;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

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

    /**
     * Constructor.
     *
     * @param ClonerInterface          $cloner
     * @param DataDumperInterface|null $dumper
     */
    public function __construct(ClonerInterface $cloner, DataDumperInterface $dumper = null, Users $users, $debugShowLoggedoff)
    {
        parent::__construct($cloner);
        $this->cloner = $cloner;
        $this->dumper = $dumper ?: new HtmlDumper();
        $this->users = $users;
        $this->debugShowLoggedoff = $debugShowLoggedoff;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(\Twig_Environment $env, $context)
    {
        if (!$env->isDebug() || (($this->users->getCurrentUser() == null) && !$this->debugShowLoggedoff)) {
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
