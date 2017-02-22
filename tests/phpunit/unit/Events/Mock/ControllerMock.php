<?php

namespace Bolt\Tests\Events\Mock;

use Silex\Application;
use Silex\ControllerProviderInterface;

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
