<?php
namespace Bolt\Controllers\Backend;

use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Backend controller for database manipulation routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Database extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/dbcheck', 'controller.backend.database:actionCheck')
            ->bind('dbcheck');

        $c->post('/dbupdate', 'controller.backend.database:actionUpdate')
            ->bind('dbupdate');

        $c->get('/dbupdate_result', 'controller.backend.database:actionUpdateResult')
            ->bind('dbupdate_result');
    }

    /*
     * Routes
     */

    /**
     * Check the database for missing tables and columns.
     *
     * Does not do actual repairs.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function actionCheck()
    {
        list($messages, $hints) = $this->app['integritychecker']->checkTablesIntegrity(true, $this->app['logger']);

        $context = array(
            'modifications_made'     => null,
            'modifications_required' => $messages,
            'modifications_hints'    => $hints,
        );

        return $this->render('dbcheck/dbcheck.twig', $context);
    }

    /**
     * Check the database, create tables, add missing/new columns to tables.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionUpdate(Request $request)
    {
        $output = $this->app['integritychecker']->repairTables();

        // If 'return=edit' is passed, we should return to the edit screen.
        // We do redirect twice, yes, but that's because the newly saved
        // contenttype.yml needs to be re-read.
        $return = $request->get('return');
        if ($return === 'edit') {
            if (empty($output)) {
                $content = Trans::__('Your database is already up to date.');
            } else {
                $content = Trans::__('Your database is now up to date.');
            }
            $this->addFlash('success', $content);

            return $this->redirectToRoute('fileedit', array('file' => 'app/config/contenttypes.yml'));
        } else {
            return $this->redirectToRoute('dbupdate_result', array('messages' => json_encode($output)));
        }
    }

    /**
     * Show the result of database updates.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function actionUpdateResult(Request $request)
    {
        $context = array(
            'modifications_made'     => json_decode($request->get('messages')),
            'modifications_required' => null,
        );

        return $this->render('dbcheck/dbcheck.twig', $context);
    }
}
