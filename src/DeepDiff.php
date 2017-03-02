<?php

namespace Bolt;

use Bolt\Helpers\Arr;
use Bolt\Helpers\Deprecated;

/**
 * @deprecated since 3.3, to be removed in 4.0.
 */
class DeepDiff
{
    /**
     * @deprecated since 3.3, to be removed in 4.0.
     *
     * @param array $a
     * @param array $b
     *
     * @return array
     */
    public static function diff($a, $b)
    {
        Deprecated::method(3.3);

        return Arr::deepDiff($a, $b);
    }
}
