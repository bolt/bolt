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
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * Passed in console paramters
     *
     * @var array
     */
    private $param;

    /**
     * The next elegible run time for each interim
     *
     * @var array
     */
    private $next_run_time;

    /**
     * True for a required database insert
     *
     * @var boolean
     */
    private $insert;

    /**
     * @var string
     */
    private $tablename;

    /**
     * The start of the execution time for this cron instance
     *
     * @var string
     */
    private $runtime;

    /**
     * @var string
     */
    private $cron_hour;

    /**
     * @var array
     */
    public $lastruns = array();

    public function __construct(Silex\Application $app, OutputInterface $output = null, $param = false)
    {
        $this->app = $app;
        $this->output = $output;
        $this->param = $param;
        $this->runtime = time();
        $this->next_run_time = array(
            CronEvents::CRON_HOURLY  => 0,
            CronEvents::CRON_DAILY   => 0,
            CronEvents::CRON_WEEKLY  => 0,
            CronEvents::CRON_MONTHLY => 0,
            CronEvents::CRON_YEARLY  => 0);

        $this->setTableName();

        // Get schedules
        $this->getNextRunTimes();

        // Time of day for daily, weekly, monthly and yearly jobs
        $this->getScheduleThreshold();

        // Call out
        $this->execute();
    }

    public function execute()
    {
        $event = new CronEvent($this->app, $this->output);

        // Process event listeners
        if ($this->isExecutable(CronEvents::CRON_HOURLY)) {
            $this->notify("Running Cron Hourly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_HOURLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_HOURLY);
            }

            $this->setLastRun(CronEvents::CRON_HOURLY);
        }

        if ($this->isExecutable(CronEvents::CRON_DAILY)) {
            $this->notify("Running Cron Daily Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_DAILY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_DAILY);
            }

            $this->setLastRun(CronEvents::CRON_DAILY);
        }

        if ($this->isExecutable(CronEvents::CRON_WEEKLY)) {
            $this->notify("Running Cron Weekly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_WEEKLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_WEEKLY);
            }

            $this->setLastRun(CronEvents::CRON_WEEKLY);
        }

        if ($this->isExecutable(CronEvents::CRON_MONTHLY)) {
            $this->notify("Running Cron Monthly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_MONTHLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_MONTHLY);
            }

            $this->setLastRun(CronEvents::CRON_MONTHLY);
        }

        if ($this->isExecutable(CronEvents::CRON_YEARLY)) {
            $this->notify("Running Cron Yearly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_YEARLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_YEARLY);
            }

            $this->setLastRun(CronEvents::CRON_YEARLY);
        }
    }

    /**
     * Test whether or not to call dispatcher
     *
     * @param  string  $name The cron event name
     * @return boolean Dispatch event or not
     */
    private function isExecutable($name)
    {
        if ($this->param['run'] && $this->param['event'] == $name) {
            return true;
        } elseif ($this->app['dispatcher']->hasListeners($name)) {
            if ($name == CronEvents::CRON_HOURLY && $this->next_run_time[CronEvents::CRON_HOURLY] <= $this->runtime) {
                return true;
            } elseif (time() > $this->cron_hour && $this->next_run_time[$name] <= $this->runtime) {
                // Only run non-hourly event jobs if we've passed our cron hour today
                return true;
            }
        }

        return false;
    }

    /**
     * Get our configured hour and convert it to UNIX time
     */
    private function getScheduleThreshold()
    {
        $hour = $this->app['config']->get('general/cron_hour');

        if (empty($hour)) {
            $this->cron_hour = strtotime("03:00");
        } elseif (is_numeric($hour)) {
            $this->cron_hour = strtotime($hour . ":00");
        } elseif (is_string($hour)) {
            $this->cron_hour = strtotime($hour);
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

        $this->app['log']->add($msg, 0, false, '');
    }

    /**
     * Set the formatted name of our table
     */
    private function setTableName()
    {
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");

        if ($prefix[strlen($prefix) - 1] != "_") {
            $prefix .= "_";
        }

        $this->tablename = $prefix . "cron";
    }

    /**
     * Query table for next run time of each interim
     */
    private function getNextRunTimes()
    {
        foreach ($this->next_run_time as $interim => $date) {
            // Handle old style naming
            $oldname = strtolower(str_replace('cron.', '', $interim));

            $query =
                "SELECT lastrun, interim " .
                "FROM {$this->tablename} " .
                "WHERE (interim = :interim OR interim = :oldname) " .
                "ORDER BY lastrun DESC";

            $result = $this->app['db']->fetchAssoc($query, array('interim' => $interim, 'oldname' => $oldname));

            // If we get an empty result for the interim, set it to the current
            // run time and notify the update method to do an INSERT rather than
            // an UPDATE.
            if (empty($result)) {
                $this->next_run_time[$interim] = $this->runtime;
                $this->insert[$interim] = true;
            } else {
                $this->next_run_time[$interim] = $this->getNextIterimRunTime($interim, $result['lastrun']);

                // @TODO remove this in v3.0
                // Update old record types
                if ($result['interim'] == $oldname) {
                    $this->insert[$interim] = true;
                } else {
                    $this->insert[$interim] = false;
                }
            }
        }
    }

    /**
     * Get the next run time for a given interim
     *
     * @param  string  $interim       The interim; CRON_HOURLY, CRON_DAILY, CRON_WEEKLY, CRON_MONTHLY or CRON_YEARLY
     * @param  string  $last_run_time The last execution time of the interim
     * @return integer The UNIX timestamp for the interims next valid execution time
     */
    private function getNextIterimRunTime($interim, $last_run_time)
    {
        if ($interim == CronEvents::CRON_HOURLY) {
            // For hourly we just default to the turn of the hour
            $last_cron_hour  = strtotime(date('Y-m-d H', strtotime($last_run_time)) . ':00:00');

            return strtotime("+1 hour", $last_cron_hour);
        } else {
            // Get the cron time of the last run time/date
            $last_cron_hour  = strtotime(date('Y-m-d', strtotime($last_run_time)) . ' ' . $this->cron_hour);

            if ($interim == CronEvents::CRON_DAILY) {
                return strtotime("+1 day", $last_cron_hour);
            } elseif ($interim == CronEvents::CRON_WEEKLY) {
                return strtotime("+1 week", $last_cron_hour);
            } elseif ($interim == CronEvents::CRON_MONTHLY) {
                return strtotime("+1 month", $last_cron_hour);
            } elseif ($interim == CronEvents::CRON_YEARLY) {
                return strtotime("+1 year", $last_cron_hour);
            }
        }
    }

    /**
     * Update table for last run time of each interim
     */
    private function setLastRun($interim)
    {
        // Define the mapping
        $map = array(
            'interim'  => $interim,
            'lastrun'   => date('Y-m-d H:i:s', $this->runtime),
        );

        // Insert or update as required
        if ($this->insert[$interim] === true) {
            $this->app['db']->insert($this->tablename, $map);
        } else {
            $this->app['db']->update($this->tablename, $map, array('interim' => $interim));
        }
    }

    /**
     * Provide feedback on exceptions in cron jobs
     *
     * @param \Exception $e       The passed exception
     * @param string     $interim The cron handler name
     */
    private function handleError(\Exception $e, $interim)
    {
        // Console feedback
        $this->output->writeln('<error>A ' . $interim . ' job failed. The exception returned was:</error>');
        $this->output->writeln('<error>    ' . $e->getMessage() . '</error>');
        $this->output->writeln('<error>Backtrace:</error>');
        $this->output->writeln('<error>' . $e->getTraceAsString() . '</error>');

        // Application log
        $this->app['log']->add('A ' . $interim . ' job failed', 2, false, substr($e->getTraceAsString(), 0, 1024));
    }
}
