<?php
namespace Bolt\Controllers\Backend;

use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Backend controller for authentication routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Authentication extends BackendBase
{
    protected function addControllers(ControllerCollection $c)
    {
    }

    /*
     * Routes
     */

    /**
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function action(Request $request)
    {
    }
}
