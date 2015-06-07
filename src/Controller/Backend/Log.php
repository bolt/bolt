<?php
namespace Bolt\Controller\Backend;

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

        $activity = $this->manager()->getActivity('change', 16);

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
        $entry = $this->changeLog()->getChangelogEntry($contenttype, $contentid, $id);
        if (empty($entry)) {
            $error = Trans::__("The requested changelog entry doesn't exist.");

            $this->abort(Response::HTTP_NOT_FOUND, $error);
        }
        $prev = $this->changeLog()->getPrevChangelogEntry($contenttype, $contentid, $id);
        $next = $this->changeLog()->getNextChangelogEntry($contenttype, $contentid, $id);

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

        $level = $request->query->get('level');
        $context = $request->query->get('context');

        $activity = $this->manager()->getActivity('system', 16, $level, $context);

        return $this->render('activity/systemlog.twig', ['entries' => $activity]);
    }

    /**
     * @return \Bolt\Logger\ChangeLog
     */
    protected function changeLog()
    {
        return $this->app['logger.manager.change'];
    }

    /**
     * @return \Bolt\Logger\Manager
     */
    protected function manager()
    {
        return $this->app['logger.manager'];
    }

    /**
     * Get the listing to pass to the renderer.
     *
     * @param string              $contenttype The content type slug
     * @param integer             $contentid   The content ID
     * @param integer|string|null $page        The page number
     */
    private function getChangeRecordListing($contenttype, $contentid, $page)
    {
        $pagination = $this->getPagination($page);
        $queryOptions = $this->getQueryOptions($pagination);

        // Now here things diverge.
        if (empty($contenttype)) {
            // Case 1: No content type given, show from *all* items. This is easy:
            $data = [
                'title'   => Trans::__('All content types'),
                'entries' => $this->changeLog()->getChangelog($queryOptions),
                'count'   => $this->changeLog()->countChangelog(),
            ];
        } else {
            $data = $this->getListingData($contenttype, $contentid, $queryOptions);
        }

        $context = [
            'contenttype' => ['slug' => $contenttype],
            'entries'     => $data['entries'],
            'content'     => $data['content'],
            'title'       => $data['title'],
            'currentpage' => $pagination['page'],
            'pagecount'   => $pagination['limit'] ? ceil($data['count'] / $pagination['limit']) : null
        ];

        return $context;
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

    /**
     * Get the listing data such as title and count.
     *
     * @param string  $contenttype  The content type slug
     * @param integer $contentid    The content ID
     * @param array   $queryOptions
     *
     * @return array
     */
    private function getListingData($contenttype, $contentid, array $queryOptions)
    {
        // We have a content type, and possibly a contentid.
        $content = null;
        $contenttypeObj = $this->getContentType($contenttype);
        if ($contentid) {
            $content = $this->getContent($contenttype, ['id' => $contentid, 'hydrate' => false]);
            $queryOptions['contentid'] = $contentid;
        }

        // Getting a slice of data and the total count
        $logEntries = $this->changeLog()->getChangelogByContentType($contenttype, $queryOptions);
        $itemcount = $this->changeLog()->countChangelogByContentType($contenttype, $queryOptions);

        // The page title we're sending to the template depends on a few things:
        // If no contentid is given, we'll use the plural form of the content
        // type; otherwise, we'll derive it from the changelog or content item
        // itself.
        if ($contentid) {
            if ($content) {
                // content item is available: get the current title
                $title = $content->getTitle();
            } else {
                // content item does not exist (anymore).
                if (empty($logEntries)) {
                    // No item, no entries - phew. Content type name and ID
                    // will have to do.
                    $title = $contenttypeObj['singular_name'] . ' #' . $contentid;
                } else {
                    // No item, but we can use the most recent title.
                    $title = $logEntries[0]['title'];
                }
            }
        } else {
            // We're displaying all changes for the entire content type,
            // so the plural name is most appropriate.
            $title = $contenttypeObj['name'];
        }

        return ['content' => $content, 'title' => $title, 'entries' => $logEntries, 'count' => $itemcount];
    }
}
