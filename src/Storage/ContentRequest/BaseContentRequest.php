<?php

namespace Bolt\Storage\ContentRequest;

use Silex\Application;

/**
 * Base class for ContentRequest
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class BaseContentRequest
{
    /** @var Application $app */
    protected $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
