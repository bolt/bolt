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
        return static::isZone($request, static::FRONTEND);
    }

    public function isBackend(Request $request)
    {
        return static::isZone($request, static::BACKEND);
    }

    public function isAsync(Request $request)
    {
        return static::isZone($request, static::ASYNC);
    }

    protected static function isZone(Request $request, $value)
    {
        return $request->attributes->get(static::KEY) === $value;
    }
}
