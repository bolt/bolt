<?php

namespace Bolt\Nut;

use Bolt\Application;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    protected $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }
}
