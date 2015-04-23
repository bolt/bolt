<?php
namespace Bolt\Controllers\Backend;

use Bolt\Controllers\Base;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for database manipulation routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Database extends Base
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
     * Check the database for missing tables and columns.
     *
     * Does not do actual repairs.
     *
     * @return \Twig_Markup
     */
    public function actionCheck()
    {
        list($messages, $hints) = $this->app['integritychecker']->checkTablesIntegrity(true, $this->app['logger']);

        $context = array(
            'modifications_made'     => null,
            'modifications_required' => $messages,
            'modifications_hints'    => $hints,
        );

        return $this->render('dbcheck/dbcheck.twig', array('context' => $context));
    }
}
