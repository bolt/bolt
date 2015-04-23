<?php
namespace Bolt\Controllers\Backend;

use Bolt\Controllers\Base;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for basic backend routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Backend extends Base
{
    /**
     * @see \Bolt\Controllers\Base::addControllers()
     *
     * @param ControllerCollection $c
     */
    protected function addControllers(ControllerCollection $c)
    {
        return $c;
    }

    /*
     * Routes
     */

    /**
     * About page route.
     *
     * @return \Twig_Markup
     */
    public function actionAbout()
    {
        $this->render('about/about.twig');
    }

    /**
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function action(Request $request)
    {
    }

    /**
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function action(Request $request)
    {
    }

    /**
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function action(Request $request)
    {
    }
}
