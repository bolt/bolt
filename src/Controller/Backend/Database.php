<?php
namespace Bolt\Controller\Backend;

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
        $c->get('/dbcheck', 'check')
            ->bind('dbcheck');

        $c->post('/dbupdate', 'update')
            ->bind('dbupdate');

        $c->get('/dbupdate_result', 'updateResult')
            ->bind('dbupdate_result');
    }

    /**
     * Check the database for missing tables and columns.
     *
     * Does not do actual repairs.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function check()
    {
        $response = $this->schemaManager()->checkTablesIntegrity(true);

        $context = [
            'modifications_made'     => null,
            'modifications_required' => $response->getResponseStrings(),
            'modifications_hints'    => $response->getHints(),
        ];

        return $this->render('@bolt/dbcheck/dbcheck.twig', $context);
    }

    /**
     * Check the database, create tables, add missing/new columns to tables.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function update(Request $request)
    {
        $output = $this->schemaManager()->repairTables()->getResponseStrings();

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
            $this->flashes()->success($content);

            return $this->redirectToRoute('fileedit', ['namespace' => 'config', 'file' => 'contenttypes.yml']);
        } else {
            $this->session()->set('dbupdate_result', $output);

            return $this->redirectToRoute('dbupdate_result');
        }
    }

    /**
     * Show the result of database updates.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function updateResult()
    {
        $output = $this->session()->get('dbupdate_result', []);

        $context = [
            'modifications_made'     => $output,
            'modifications_required' => null,
        ];

        return $this->render('@bolt/dbcheck/dbcheck.twig', $context);
    }

    /**
     * @return \Bolt\Storage\Database\Schema\Manager
     */
    protected function schemaManager()
    {
        return $this->app['schema'];
    }
}
