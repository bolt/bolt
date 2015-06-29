<?php
namespace Bolt\Controller\Backend;

use Bolt\Pager;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for logging routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Log extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/changelog', 'changeOverview')
            ->bind('changelog');

        $c->get('/changelog/{contenttype}/{contentid}/{id}', 'changeRecord')
            ->assert('id', '\d*')
            ->bind('changelogrecordsingle');

        $c->get('/changelog/{contenttype}/{contentid}', 'changeRecordListing')
            ->value('contentid', '0')
            ->value('contenttype', '')
            ->bind('changelogrecordall');

        $c->get('/systemlog', 'systemOverview')
            ->bind('systemlog');
    }

    /**
     * Change log overview route.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeOverview(Request $request)
    {
        $action = $request->query->get('action');

        if ($action == 'clear') {
            $this->manager()->clear('change');
            $this->flashes()->success(Trans::__('The change log has been cleared.'));

            return $this->redirectToRoute('changelog');
        } elseif ($action == 'trim') {
            $this->manager()->trim('change');
            $this->flashes()->success(Trans::__('The change log has been trimmed.'));

            return $this->redirectToRoute('changelog');
        }

        // Test/get page number
        $param = Pager::makeParameterId('activity');
        $page = ($request->query) ? $request->query->get($param, $request->query->get('page', 1)) : 1;

        $activity = $this->manager()->getActivity('change', $page, 16);

        return $this->render('activity/changelog.twig', ['entries' => $activity]);
    }

    /**
     * Show a single change log entry.
     *
     * @param Request $request     The Symfony Request
     * @param string  $contenttype The content type slug
     * @param integer $contentid   The content ID
     * @param integer $id          The changelog entry ID
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function changeRecord(Request $request, $contenttype, $contentid, $id)
    {
        $entry = $this->changeLogRepository()->getChangelogEntry($contenttype, $contentid, $id, '=');
        if (empty($entry)) {
            $error = Trans::__("The requested changelog entry doesn't exist.");

            $this->abort(Response::HTTP_NOT_FOUND, $error);
        }
        $prev = $this->changeLogRepository()->getChangelogEntry($contenttype, $contentid, $id, '<');
        $next = $this->changeLogRepository()->getChangelogEntry($contenttype, $contentid, $id, '>');

        $context = [
            'contenttype' => ['slug' => $contenttype],
            'entry'       => $entry,
            'next_entry'  => $next,
            'prev_entry'  => $prev
        ];

        return $this->render('changelog/changelogrecordsingle.twig', $context);
    }

    /**
     * Show a list of changelog entries.
     *
     * @param Request $request     The Symfony Request
     * @param string  $contenttype The content type slug
     * @param integer $contentid   The content ID
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function changeRecordListing(Request $request, $contenttype, $contentid)
    {
        // We have to handle three cases here:
        // - $contenttype and $contentid given: get changelog entries for *one* content item
        // - only $contenttype given: get changelog entries for all items of that type
        // - neither given: get all changelog entries

        $page = $request->get('page');
        $context = $this->getChangeRecordListing($contenttype, $contentid, $page);

        return $this->render('changelog/changelogrecordall.twig', $context);
    }

    /**
     * System log overview route
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function systemOverview(Request $request)
    {
        $action = $request->query->get('action');

        if ($action == 'clear') {
            $this->manager()->clear('system');
            $this->flashes()->success(Trans::__('The system log has been cleared.'));

            return $this->redirectToRoute('systemlog');
        } elseif ($action == 'trim') {
            $this->manager()->trim('system');
            $this->flashes()->success(Trans::__('The system log has been trimmed.'));

            return $this->redirectToRoute('systemlog');
        }

        $level = $request->query->get('level', null) ?: null;
        $context = $request->query->get('context', null) ?: null;

        // Test/get page number
        $param = Pager::makeParameterId('activity');
        $page = ($request->query) ? $request->query->get($param, $request->query->get('page', 1)) : 1;

        $activity = $this->manager()->getActivity('system', $page, 16, $level, $context);

        return $this->render('activity/systemlog.twig', ['entries' => $activity]);
    }

    /**
     * @return \Bolt\Logger\Manager
     */
    protected function manager()
    {
        return $this->app['logger.manager'];
    }
}
