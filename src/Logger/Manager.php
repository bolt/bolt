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

        if ($log == 'change') {
            return $this->decodeChangeLog($rows);
        }

        return $rows;
    }

    /**
     * Decode JSON in change log fields.
     *
     * @param array $rows
     *
     * @return array
     */
    private function decodeChangeLog($rows)
    {
        if (!is_array($rows)) {
            return $rows;
        }

        foreach ($rows as $key => $row) {
            $rows[$key]['diff'] = json_decode($row['diff'], true);
        }

        return $rows;
    }
}
