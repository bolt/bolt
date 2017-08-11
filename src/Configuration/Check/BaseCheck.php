<?php

namespace Bolt\Configuration\Check;

use Bolt\Common\Deprecated;
use Silex\Application;

/**
 * Base class for checks.
 *
 * @deprecated Since 3.4, to be removed in 4.0
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class BaseCheck
{
    /** @var Application */
    protected $app;
    /** @var array */
    protected $options;
    /** @var Result[] */
    protected $results;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        Deprecated::cls(__CLASS__, 3.4);
        $this->app = $app;
    }

    /**
     * Getter for the result container.
     *
     * @return \Bolt\Configuration\Check\Result
     */
    protected function createResult()
    {
        $result = new Result();
        $this->results[] = $result;

        return $result;
    }
}
