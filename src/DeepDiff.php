<?php

namespace Bolt;

use Bolt\Common\Deprecated;
use Bolt\Logger\Handler\RecordChangeHandler;

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

        $cls = new \ReflectionClass(RecordChangeHandler::class);
        $method = $cls->getMethod('diff');
        $method->setAccessible(true);
        $obj = $cls->newInstanceWithoutConstructor();

        return $method->invoke($obj, $a, $b);
    }
}
