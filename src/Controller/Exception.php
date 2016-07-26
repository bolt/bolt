<?php

namespace Bolt\Controller;

use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception controller.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Exception extends Base
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->value(Zone::KEY, Zone::FRONTEND);

        $c->get('/exception/{key}', 'exception')
            ->bind('exceptionRoute');
    }

    public function exception(Request $request, $key)
    {
        return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
