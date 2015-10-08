<?php

namespace Bolt\Controller\Async;

use Bolt\Storage\Entity\Content;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Async controller for record manipulation routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Records extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->method('POST');

        $c->post('/content/{action}/{contenttypeslug}/{id}', 'modify')
            ->bind('contentaction');
    }
}
