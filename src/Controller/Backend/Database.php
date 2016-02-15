<?php
namespace Bolt\Controller\Backend;

use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Backend controller for database manipulation routes.
 *
 * Prior to v3.0 this functionality primarily existed in the monolithic
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
     * @param Request $request
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function check(Request $request)
    {
        /** @var $response \Bolt\Storage\Database\Schema\SchemaCheck */
        $check = $this->app['schema']->check();

        $context = [
            'changes' => null,
            'check'   => $check,
            'debug'   => $request->query->has('debug'),
            'alters'  => $this->app['schema.comparator']->getAlters(),
            'creates' => $this->app['schema.comparator']->getCreates(),
            'diffs'   => $this->app['schema.comparator']->getDiffs(),
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
        $output = $this->schemaManager()->update();
        $this->session()->set('dbupdate_result', $output->getResponseStrings());

        return $this->redirectToRoute('dbupdate_result');
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
            'changes' => $output,
            'check'   => null,
            'debug'   => false,
            'alters'  => null,
            'creates' => null,
            'diffs'   => null,
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
