<?php

namespace Bolt\Controllers;

use Silex;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Bolt\CronEvent;
use Bolt\CronEvents;

/**
 * Simple cron dispatch class for Bolt
 *
 * To create a listener you need to something similar in your class:
 *      use Bolt\CronEvents;
 *      $this->app['dispatcher']->addListener(CronEvents::CRON_INTERVAL, array($this, 'myJobCallbackMethod'));
 *
 * CRON_INTERVAL should be replace with one of the following:
 *      * CRON_HOURLY
 *      * CRON_DAILY
 *      * CRON_WEEKLY
 *      * CRON_MONTHLY
 *      * CRON_YEARLY
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 * @property \Bolt\Application $app
 **/
class Cron extends Event
{
    private $app;
    private $output;
    private $intervals;
    private $insert;
    private $prefix;
    private $tablename;
    private $runtime;

    public $lastruns = array();

    public function __construct(Silex\Application $app, OutputInterface $output = null)
    {
        $this->app = $app;
        $this->output = $output;
        $this->runtime = date("Y-m-d H:i:s", time());
        $this->intervals = array('hourly' => 0, 'daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0);
        $this->setTableName();

        // Get schedules
        $this->getLastRun();

        // Time of day for daily, weekly, monthly and yearly jobs
        $this->getScheduleThreshold();

        // Call out
        $this->execute();
    }

    public function execute()
    {
        $event = new CronEvent($this->app, $this->output);

        // Process event listeners
        if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_HOURLY) && $this->intervals['hourly'] < strtotime("-1 hour")) {
            $this->notify("Running Cron Hourly Jobs");
            $this->app['dispatcher']->dispatch(CronEvents::CRON_HOURLY, $event);
            $this->setLastRun('hourly');
        }

        // Only check the running of these if we've passed our threshold hour today
        if (time() > $this->threshold) {
            if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_DAILY) && $this->intervals['daily'] < strtotime("-1 day")) {
                $this->notify("Running Cron Daily Jobs");
                $this->app['dispatcher']->dispatch(CronEvents::CRON_DAILY, $event);
                $this->setLastRun('daily');
            }

            if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_WEEKLY) && $this->intervals['weekly'] < strtotime("-1 week")) {
                $this->notify("Running Cron Weekly Jobs");
                $this->app['dispatcher']->dispatch(CronEvents::CRON_WEEKLY, $event);
                $this->setLastRun('weekly');
            }

            if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_MONTHLY) && $this->intervals['monthly'] < strtotime("-1 month")) {
                $this->notify("Running Cron Monthly Jobs");
                $this->app['dispatcher']->dispatch(CronEvents::CRON_MONTHLY, $event);
                $this->setLastRun('monthly');
            }

            if ($this->app['dispatcher']->hasListeners(CronEvents::CRON_YEARLY) && $this->intervals['yearly'] < strtotime("-1 year")) {
                $this->notify("Running Cron Yearly Jobs");
                $this->app['dispatcher']->dispatch(CronEvents::CRON_YEARLY, $event);
                $this->setLastRun('yearly');
            }
        }
    }

    /**
     * Get our configured hour and convert it to UNIX time
     */
    private function getScheduleThreshold()
    {
        $hour = $this->app['config']->get('general/cron_hour');

        if (empty($hour)) {
            $this->threshold = strtotime("03:00");
        } elseif (is_numeric($hour)) {
            $this->threshold = strtotime($hour . ":00");
        } elseif (is_string($hour)) {
            $this->threshold = strtotime($hour);
        }
    }

    /**
     * If we're passed an OutputInterface, we're called from Nut and can notify
     * the end user
     */
    private function notify($msg)
    {
        if ($this->output !== false) {
            $this->output->writeln("<info>{$msg}</info>");
        }
    }

    /**
     * Set the formatted name of our table
     */
    private function setTableName()
    {
        $this->prefix = $this->app['config']->get('general/database/prefix', "bolt_");

        if ($this->prefix[strlen($this->prefix) - 1] != "_") {
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
                "SELECT lastrun " .
                "FROM {$this->tablename} " .
                "WHERE interval = '{$interval}' " .
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
            $query = "INSERT INTO {$this->tablename} " .
            "(interval, lastrun) " .
            "VALUES (:interval, :lastrun)";
        } else {
            $query = "UPDATE {$this->tablename} " .
            "SET lastrun = :lastrun, lastrun = :lastrun " .
            "WHERE interval = :interval ";
        }

        // Define the mapping
        $map = array(
            ':interval'  => $interval,
            ':lastrun'   => $this->runtime,
        );

        // Write to db
        $this->app['db']->executeUpdate($query, $map);
    }
}
