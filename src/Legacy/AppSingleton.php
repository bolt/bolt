<?php

namespace Bolt\Legacy;

use Silex\Application;

/**
 * Don't use this. Period.
 *
 * This is the same singleton ResourceManager had. It has just been pulled out so other
 * deprecated parts of the codebase don't have to be coupled to ResourceManager.
 *
 * @deprecated Deprecated since 3.3, to be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class AppSingleton
{
    /** @var Application */
    private static $app;

    /**
     * Wrong.
     *
     * @return Application
     */
    public static function get()
    {
        if (!static::$app) {
            throw new \LogicException("The Application object isn't initialized yet");
        }

        return static::$app;
    }

    public static function set(Application $app)
    {
        static::$app = $app;
    }
}
