<?php

namespace Bolt;

/**
 * Bolt's current version.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class Version
{
    /**
     * Bolt's version constant.
     *
     * This should take the form of:
     *   x.y.z [[alpha|beta|RC|patch] n]
     *
     * e.g. versions for:
     *   Stable      — 3.0.0
     *   Development — 3.1.0 alpha 1
     */
    const VERSION = '3.0.11';

    /**
     * Whether this release is a stable one.
     *
     * @return bool
     */
    public static function isStable()
    {
        return (bool) preg_match('/^[0-9\.]+$/', static::VERSION);
    }

    /**
     * Compares a version to Bolt's version.
     *
     * @param string $version  The version to compare.
     * @param string $operator The comparison operator: <, <=, >, >=, ==, !=
     *
     * @return bool Whether the comparison succeeded.
     */
    public static function compare($version, $operator)
    {
        $currentVersion = str_replace(' ', '', strtolower(static::VERSION));
        $version = str_replace(' ', '', strtolower($version));

        return version_compare($version, $currentVersion, $operator);
    }

    /**
     * Returns a version formatted for composer.
     *
     * @return string
     */
    public static function forComposer()
    {
        if (strpos(static::VERSION, ' ') === false) {
            return static::VERSION;
        }

        $version = explode(' ', static::VERSION, 2);

        return $version[0];
    }

    /**
     * @deprecated since 3.0, to be removed in 4.0.
     *
     * @return string|null
     */
    public static function name()
    {
        if (strpos(static::VERSION, ' ') === false) {
            return null;
        }

        return explode(' ', static::VERSION)[1];
    }

    /**
     * Must not be instantiated.
     */
    private function __construct()
    {
    }
}
