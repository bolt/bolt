<?php
namespace Bolt\Controllers\Backend;

use Bolt\Content;
use Bolt\Controllers\Base;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Content extends Base
{
    /**
     * @param ControllerCollection $c
     */
    protected function addControllers(ControllerCollection $c)
    {
        return $c;
    }
}
