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
     * @param Request $request
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

        $options = [
            'contenttype' => $request->query->get('contenttype'),
            'contentid'   => $request->query->get('contentid'),
            'ownerid'     => $request->query->get('ownerid')
        ];
        $activity = $this->manager()->getActivity('change', $page, 16, $options);

        return $this->render('@bolt/activity/changelog.twig', ['entries' => $activity]);
    }

    /**
     * Show a single change log entry.
     *
     * @param Request $request
     * @param string  $contenttype ContentType slug
     * @param integer $contentid   Content record ID
     * @param integer $id          The change log entry ID
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function changeRecord(Request $request, $contenttype, $contentid, $id)
    {
        $entry = $this->changeLogRepository()->getChangeLogEntry($contenttype, $contentid, $id, '=');
        if (empty($entry)) {
            $error = Trans::__("The requested changelog entry doesn't exist.");

            $this->abort(Response::HTTP_NOT_FOUND, $error);
        }
        $prev = $this->changeLogRepository()->getChangeLogEntry($contenttype, $contentid, $id, '<');
        $next = $this->changeLogRepository()->getChangeLogEntry($contenttype, $contentid, $id, '>');

        $context = [
            'contenttype' => ['slug' => $contenttype],
            'entry'       => $entry,
            'next_entry'  => $next,
            'prev_entry'  => $prev
        ];

        return $this->render('@bolt/changelog/changelog_record_single.twig', $context);
    }

    /**
     * Show a list of changelog entries.
     *
     * @param Request $request
     * @param string  $contenttype ContentType slug
     * @param integer $contentid   Content record ID
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
        $pagination = $this->getPagination($page);
        $queryOptions = $this->getQueryOptions($pagination);

        if (empty($contenttype)) {
            // Case 1: No content type given, show from *all* items. This is easy:
            $data = [
                'title'   => Trans::__('All content types'),
                'entries' => $this->changeLogRepository()->getChangeLog($queryOptions),
                'count'   => $this->changeLogRepository()->countChangeLog(),
            ];
        } else {
            $contenttype = $this->getContentType($contenttype);
            $data = $this->manager()->getListingData($contenttype, $contentid, $queryOptions);
        }

        $context = [
            'contenttype' => $contenttype,
            'entries'     => $data['entries'],
            'content'     => $data['content'],
            'title'       => $data['title'],
            'currentpage' => $pagination['page'],
            'pagecount'   => $pagination['limit'] ? ceil($data['count'] / $pagination['limit']) : null
        ];

        return $this->render('@bolt/changelog/changelog_record_all.twig', $context);
    }

    /**
     * System log overview route
     *
     * @param Request $request
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

        // Test/get page number
        $param = Pager::makeParameterId('activity');
        $page = ($request->query) ? $request->query->get($param, $request->query->get('page', 1)) : 1;
        $options = [
            'level'   => $request->query->get('level'),
            'context' => $request->query->get('context')
        ];

        $activity = $this->manager()->getActivity('system', $page, 16, $options);

        return $this->render('@bolt/activity/systemlog.twig', ['entries' => $activity]);
    }

    /**
     * @return \Bolt\Logger\Manager
     */
    protected function manager()
    {
        return $this->app['logger.manager'];
    }

    /**
     * @return \Bolt\Storage\Repository\LogChangeRepository
     */
    protected function changeLogRepository()
    {
        return $this->storage()->getRepository('Bolt\Storage\Entity\LogChange');
    }

    /**
     * @return \Bolt\Storage\Repository\LogSystemRepository
     */
    protected function systemLogRepository()
    {
        return $this->storage()->getRepository('Bolt\Storage\Entity\LogSystem');
    }

    /**
     * Calculate pagination parameters.
     *
     * @param integer|string|null $page
     *
     * @return array
     */
    private function getPagination($page)
    {
        $limit = 5;
        if ($page !== null) {
            if ($page === 'all') {
                $limit = null;
                $page = null;
            } else {
                $page = intval($page);
            }
        } else {
            $page = 1;
        }

        return ['page' => $page, 'limit' => $limit];
    }

    /**
     * Calculate the query options.
     *
     * @param array $pagination
     *
     * @return array
     */
    private function getQueryOptions($pagination)
    {
        // Some options that are the same for all three cases
        $options = [
            'order'     => 'date',
            'direction' => 'DESC'
        ];

        if ($pagination['limit']) {
            $options['limit'] = $pagination['limit'];
        }
        if ($pagination['page'] > 0 && $pagination['limit']) {
            $options['offset'] = ($pagination['page'] - 1) * $pagination['limit'];
        }

        return $options;
    }
}
