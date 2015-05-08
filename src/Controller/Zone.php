<?php
namespace Bolt\Controller;

use Symfony\Component\HttpFoundation\Request;

class Zone
{
    const KEY = 'zone';

    const FRONTEND = 'frontend';
    const BACKEND = 'backend';
    const ASYNC = 'async';

    public static function isFrontend(Request $request)
    {
        return static::is($request, static::FRONTEND);
    }

    public static function isBackend(Request $request)
    {
        return static::is($request, static::BACKEND);
    }

    public static function isAsync(Request $request)
    {
        return static::is($request, static::ASYNC);
    }

    public static function is(Request $request, $value)
    {
        return static::get($request) === $value;
    }

    public static function get(Request $request)
    {
        return $request->attributes->get(static::KEY);
    }
}
