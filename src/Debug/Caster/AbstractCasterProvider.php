<?php

namespace Bolt\Debug\Caster;

/**
 * Abstract class providing casters.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class AbstractCasterProvider
{
    /**
     * @return callable[]
     */
    protected static function defineCasters()
    {
        return [];
    }

    public static function getCasters()
    {
        $cls = get_called_class();

        return array_map(
            function ($func) use ($cls) {
                if (method_exists($cls, $func)) {
                    return [$cls, $func];
                }

                return $func;
            },
            static::defineCasters()
        );
    }
}
