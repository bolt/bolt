<?php

namespace Bolt\Database\Migration;

use Bolt\Application;

/**
 * Database records export class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Export extends AbstractMigration
{
    /** @var Bolt\Application */
    private $app;

    /**
     * Constructor.
     *
     * @param \Bolt\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
