<?php

namespace Bolt\Helpers;

/**
 * This class provides shortcuts for trigger deprecation warnings for various things.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Deprecated extends \Bolt\Common\Deprecated
{
    /**
     * Shortcut for triggering a deprecation warning for a DI service.
     *
     * Example:
     *     Deprecated::service('foo', 3.3, 'bar'); // triggers warning: "Accessing $app['foo'] is deprecated since 3.3 and will be removed in 4.0. Use $app['bar'] instead."
     *
     * @param string     $name    the service that is deprecated
     * @param float|null $since   the version it was deprecated in
     * @param string     $suggest a service name or suggestion of what to use instead
     */
    public static function service($name, $since = null, $suggest = '')
    {
        if ($suggest && preg_match('/\s/', $suggest) === 0) {
            $suggest = sprintf("Use \$app['%s'] instead.", $suggest);
        }

        static::warn(sprintf("Accessing \$app['%s']", $name), $since, $suggest);
    }
}
