<?php

namespace Bolt;

use Silex;

/**
 * Handles translation dependent tasks
 */
class Translation
{
    private $app;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }
}
