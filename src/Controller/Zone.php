<?php
namespace Bolt\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Zone constants class to define which part of the Bolt site that a request is
 * relative to.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Zone
{
    const KEY = 'zone';

    const FRONTEND = 'frontend';
    const BACKEND = 'backend';
    const ASYNC = 'async';

    /**
     * Check if request is for frontend routes.
     *
     * @param Request $request
     *
     * @return boolean
     */
    public static function isFrontend(Request $request)
    {
        return static::is($request, static::FRONTEND);
    }

    /**
     * Check if request is for backend routes.
     *
     * @param Request $request
     *
     * @return boolean
     */
    public static function isBackend(Request $request)
    {
        return static::is($request, static::BACKEND);
    }

    /**
     * Check if request is for asynchronous/AJAX routes.
     *
     * @param Request $request
     *
     * @return boolean
     */
    public static function isAsync(Request $request)
    {
        return static::is($request, static::ASYNC);
    }

    /**
     * Check if request is for a specific zone.
     *
     * @param Request $request
     * @param string  $value
     *
     * @return boolean
     */
    public static function is(Request $request, $value)
    {
        return static::get($request) === $value;
    }

    /**
     * Get the current zone.
     *
     * @param Request $request
     *
     * @return string|null
     */
    public static function get(Request $request)
    {
        return $request->attributes->get(static::KEY);
    }

    /**
     * Set the current zone.
     *
     * @param Request $request
     * @param string  $value
     */
    public static function set(Request $request, $value)
    {
        $request->attributes->set(static::KEY, $value);
    }
}
