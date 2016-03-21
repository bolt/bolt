<?php

namespace Bolt\Extension;

use Pimple as Container;

/**
 * Storage helpers.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait StorageTrait
{
    /** @return Container */
    abstract protected function getContainer();
}
