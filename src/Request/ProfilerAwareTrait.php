<?php

namespace Bolt\Request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Profiler request aware trait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ProfilerAwareTrait
{
    /**
     * Check to see if the request has matched one of the profiler's
     * routes.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isProfilerRequest(Request $request)
    {
        $route = $request->attributes->get('_route');
        if ($route === '_wdt' || strpos($route, '_profiler') === 0) {
            return true;
        }

        return false;
    }
}
