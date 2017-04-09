<?php

namespace Bolt\Tests\Events\Mock;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

/**
 * Controller mock.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerMock implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        return new ClippyKoala();
    }
}
