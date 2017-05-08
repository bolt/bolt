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
    const VERSION = '3.2.13';

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
     * Compares a semantic version (x.y.z) against Bolt's version, given a
     * specified comparison operator.
     *
     * Note 1:
     * Be sure to include the `.z` number in the version given, as
     * omitting it can give inconsistent results.
     *
     * e.g. If the version of Bolt was '3.2.0' (or greater), then:
     *     `Version::compare('3.2', '>=');`
     * is NOT equal to, or greater than, Bolt's version.
     *
     * Note 2:
     * Pre-release versions, such as 3.2.0-beta1, are considered lower
     * than their final release counterparts (like 2.3.0). As you may notice,
     * the difference being that Bolt '3.2.0-beta1' is considered LOWER than
     * the `compare($version)` value of '3.2.0'.
     *
     * e.g. If the version of Bolt was '3.2.0 beta 1', then:
     *     `Version::compare('3.2.0', '>=');`
     * is equal to, or greater than, Bolt's version.
     *
     * @see http://semver.org/ For an explanation on semantic versioning.
     * @see http://php.net/manual/en/function.version-compare.php#refsect1-function.version-compare-notes Notes on version_compare
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
