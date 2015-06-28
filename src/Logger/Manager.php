<?php

namespace Bolt\Logger;

use Bolt\Pager;
use Bolt\Storage\Repository;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

/**
 * Bolt's logger service class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    /** @var Application */
    private $app;
    /** @var \Bolt\Storage\Repository\LogChange */
    private $changeRepository;
    /** @var \Bolt\Storage\Repository\LogSystem */
    private $systemRepository;

    /**
     * Constructor.
     *
     * @param Application $app
     * @param Repository\LogChange $changeRepository
     * @param Repository\LogSystem $systemRepository
     */
    public function __construct(Application $app, Repository\LogChange $changeRepository, Repository\LogSystem $systemRepository)
    {
        $this->app = $app;
        $this->changeRepository = $changeRepository;
        $this->systemRepository = $systemRepository;
    }

    /**
     * Trim the log.
     *
     * @param string $log
     *
     * @throws \UnexpectedValueException
     */
    public function trim($log)
    {
        $period = new \DateTime('-7 day');
        if ($log === 'change') {
            $this->changeRepository->trimLog($period);
        } elseif ($log === 'system') {
            $this->systemRepository->trimLog($period);
        } else {
            throw new \UnexpectedValueException("Invalid log type requested: $log");
        }
    }

    /**
     * Clear a log.
     *
     * @param string $log
     *
     * @throws \UnexpectedValueException
     */
    public function clear($log)
    {
        if ($log === 'change') {
            $this->changeRepository->clearLog();
        } elseif ($log === 'system') {
            $this->systemRepository->clearLog();
        } else {
            throw new \UnexpectedValueException("Invalid log type requested: $log");
        }

        $this->app['logger.system']->info(ucfirst($log) . ' log cleared.', ['event' => 'security']);
    }

    /**
     * Get a specific activity log.
     *
     * @param string  $log     The log to query.  Either 'change' or 'system'
     * @param integer $page
     * @param integer $amount  Number of results to return
     * @param integer $level
     * @param string  $context
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    public function getActivity($log, $page = 1, $amount = 10, $level = null, $context = null)
    {
        if ($log == 'change') {
            $rows = $this->changeRepository->getActivity($page, $amount, $level, $context);
            $rowcount = $this->changeRepository->getActivityCount($level, $context);
        } elseif ($log == 'system') {
            $rows = $this->systemRepository->getActivity($page, $amount, $level, $context);
            $rowcount = $this->systemRepository->getActivityCount($level, $context);
        } else {
            throw new \UnexpectedValueException("Invalid log type requested: $log");
        }

        // Set up the pager
        $pager = [
            'for'          => 'activity',
            'count'        => $rowcount,
            'totalpages'   => ceil($rowcount / $amount),
            'current'      => $page,
            'showing_from' => ($page - 1) * $amount + 1,
            'showing_to'   => ($page - 1) * $amount + count($rows)
        ];

        $this->app['storage']->setPager('activity', $pager);

        return $rows;
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
                'entries' => $this->changeRepository->getChangelog($queryOptions),
                'count'   => $this->changeRepository->countChangelog(),
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
        $logEntries = $this->changeRepository->getChangelogByContentType($contenttype, $queryOptions);
        $itemcount = $this->changeRepository->countChangelogByContentType($contenttype, $queryOptions);

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
