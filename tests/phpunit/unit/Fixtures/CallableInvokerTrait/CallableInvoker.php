<?php

namespace Bolt\Tests\Fixtures\CallableInvokerTrait;

use Bolt\Asset\CallableInvokerTrait;

/**
 * Test fixture for CallableInvokerTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CallableInvoker
{
    use CallableInvokerTrait;

    public function doInvokeCallable($callback, $callbackArguments = null)
    {
        return $this->invokeCallable($callback, $callbackArguments);
    }
}
