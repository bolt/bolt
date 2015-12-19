<?php

namespace Bolt\Extension;

use Silex\Application;

/**
 * Management class for extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    /** @var Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
