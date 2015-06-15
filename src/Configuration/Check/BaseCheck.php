<?php
namespace Bolt\Configuration\Check;

use Silex\Application;

/**
 * Base class for checks.
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
