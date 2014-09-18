<?php

namespace Bolt\Nut;

use Bolt\Application;
use Symfony\Component\Console\Command\Command;

/* @codingStandardsIgnoreStart */
abstract class BaseCommand extends Command
/* @codingStandardsIgnoreEnd */
{
    protected $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }
}
