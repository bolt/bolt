<?php

namespace Bolt\Logger;

use Bolt\Pager;
use Bolt\Storage\Repository;
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
    /** @var \Bolt\Storage\Repository\LogChangeRepository */
    private $changeRepository;
    /** @var \Bolt\Storage\Repository\LogSystemRepository */
    private $systemRepository;

    /**
     * Constructor.
     *
     * @param Application          $app
     * @param Repository\LogChange $changeRepository
     * @param Repository\LogSystem $systemRepository
     */
    public function __construct(Application $app, Repository\LogChangeRepository $changeRepository, Repository\LogSystemRepository $systemRepository)
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
     * @param array   $options
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    public function getActivity($log, $page = 1, $amount = 10, $options = [])
    {
        if ($log == 'change') {
            $rows = $this->changeRepository->getActivity($page, $amount, $options);
            $rowcount = $this->changeRepository->getActivityCount($options);
        } elseif ($log == 'system') {
            $rows = $this->systemRepository->getActivity($page, $amount, $options);
            $rowcount = $this->systemRepository->getActivityCount($options);
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
     * Get the listing data such as title and count.
     *
     * @param array   $contenttype  The ContentType
     * @param integer $contentid    The content ID
     * @param array   $queryOptions
     *
     * @return array
     */
    public function getListingData(array $contenttype, $contentid, array $queryOptions)
    {
        // We have a content type, and possibly a contentid.
        $content = null;

        if ($contentid) {
            $content = $this->app['storage']->getContent($contenttype['slug'], ['id' => $contentid, 'hydrate' => false]);
            $queryOptions['contentid'] = $contentid;
        }

        // Getting a slice of data and the total count
        $logEntries = $this->changeRepository->getChangelogByContentType($contenttype['slug'], $queryOptions);
        $itemcount = $this->changeRepository->countChangelogByContentType($contenttype['slug'], $queryOptions);

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
                    $title = $contenttype['singular_name'] . ' #' . $contentid;
                } else {
                    // No item, but we can use the most recent title.
                    $title = $logEntries[0]['title'];
                }
            }
        } else {
            // We're displaying all changes for the entire content type,
            // so the plural name is most appropriate.
            $title = $contenttype['name'];
        }

        return ['content' => $content, 'title' => $title, 'entries' => $logEntries, 'count' => $itemcount];
    }
}
