<?php

namespace Bolt\Controllers;

use Silex;
use Doctrine\DBAL\Connection as DoctrineConn;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Bolt\CronEvents;

/**
 * Simple cron dispatch class for Bolt
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 **/
class Cron extends Event
{
    private $app;
    private $intervals;
    private $insert;
    private $prefix;
    private $tablename;
    private $runtime;

    public $lastruns = array();

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->runtime = date("Y-m-d H:i:s", time());
        $this->intervals = array('hourly' => 0, 'daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0);
        $this->setTableName();

        // Get schedules
        $this->getLastRun();

        // Call out
        $this->execute();
    }

    public function execute()
    {
        // Process event listeners
        if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_HOURLY) && $this->intervals['hourly'] < strtotime("-1 hour")) {
            echo "Cron Hourly Jobs\n";
            $this->app['dispatcher']->dispatch(CronEvents::CRON_HOURLY, new \Bolt\CronEvent)->doRunJobs(CRON_HOURLY);
            $this->setLastRun('hourly');
        }

        if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_DAILY) && $this->intervals['daily'] < strtotime("-1 day")) {
            echo "Cron Daily Jobs\n";
            $this->app['dispatcher']->dispatch(CronEvents::CRON_DAILY, new \Bolt\CronEvent)->doRunJobs(CRON_DAILY);
            $this->setLastRun('daily');
        }

        if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_WEEKLY) && $this->intervals['weekly'] < strtotime("-1 week")) {
            echo "Cron Weekly Jobs\n";
            $this->app['dispatcher']->dispatch(CronEvents::CRON_WEEKLY, new \Bolt\CronEvent)->doRunJobs(CRON_WEEKLY);
            $this->setLastRun('weekly');
        }

        if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_MONTHLY) && $this->intervals['monthly'] < strtotime("-1 month")) {
            echo "Cron Monthly Jobs\n";
            $this->app['dispatcher']->dispatch(CronEvents::CRON_MONTHLY, new \Bolt\CronEvent)->doRunJobs(CRON_MONTHLY);
            $this->setLastRun('monthly');
        }

        if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_YEARLY) && $this->intervals['yearly'] < strtotime("-1 year") ) {
            echo "Cron Yearly Jobs\n";
            $this->app['dispatcher']->dispatch(CronEvents::CRON_YEARLY, new \Bolt\CronEvent)->doRunJobs(CRON_YEARLY);
            $this->setLastRun('yearly');
        }
    }


    /**
     * Set the formatted name of our table
     */
    private function setTableName()
    {
        $this->prefix = $this->app['config']->get('general/database/prefix', "bolt_");

        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }

        $this->tablename = $this->prefix . "cron";
    }


    /**
     * Query table for last run time of each interval
     */
    private function getLastRun()
    {
        foreach ($this->intervals as $interval => $date) {
            $query =
                "SELECT `lastrun` " .
                "FROM `{$this->tablename}` " .
                "WHERE `interval` = '{$interval}' " .
                "ORDER BY lastrun DESC " .
                "LIMIT 1 ";

            $result = $this->app['db']->fetchAll($query);

            // If we get an empty result for the interval, set it to the current
            // run time and notify the update method to do an INSERT rather than
            // an UPDATE.
            if (empty($result)) {
                $this->insert[$interval] = true;
            } else {
                $this->intervals[$interval] = strtotime($result[0]['lastrun']);
                $this->insert[$interval] = false;
            }
        }
    }


    /**
     * Update table for last run time of each interval
     */
    private function setLastRun($interval)
    {
        // Get appropriate query string
        if ($this->insert[$interval] === true) {
            $query = "INSERT INTO `{$this->tablename}` " .
            "(`interval`, `lastrun`) " .
            "VALUES (:interval, :lastrun)";
        } else {
            $query = "UPDATE `{$this->tablename}` " .
            "SET `lastrun` = :lastrun, `lastrun` = :lastrun " .
            "WHERE `interval` = :interval ";
        }

        // Define the mapping
        $map = array(
            ':interval'  => $interval,
            ':lastrun'   => $this->runtime,
        );

        // Write to db
        $db = $this->app['db']->executeUpdate($query, $map);
    }
}
