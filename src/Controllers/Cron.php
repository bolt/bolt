<?php

namespace Bolt\Controllers;

use Bolt\Events\CronEvent;
use Bolt\Events\CronEvents;
use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Simple cron dispatch class for Bolt.
 *
 * To create a listener you need to something similar in your class:
 *      use Bolt\Events\CronEvents;
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
 **/
class Cron extends Event
{
    /** @var \Silex\Application */
    private $app;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;

    /** @var array Passed in console paramters. */
    private $param;

    /** @var array The next elegible run time for each interim. */
    private $nextRunTime;

    /** @var boolean True for a required database insert. */
    private $insert;

    /** @var string */
    private $tablename;

    /** @var string The start of the execution time for this cron instance.*/
    private $runtime;

    /** @var string */
    private $cronHour;

    /** @var array */
    public $lastruns = array();

    /**
     * @param Application     $app
     * @param OutputInterface $output
     * @param array           $param
     */
    public function __construct(Application $app, OutputInterface $output = null, $param = array())
    {
        $this->app = $app;
        $this->output = $output;
        $this->param = $param;
        $this->runtime = time();
        $this->nextRunTime = array(
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

    /**
     * Run the jobs.
     *
     * @return void
     */
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
     * Test whether or not to call dispatcher.
     *
     * @param string $name The cron event name
     *
     * @return boolean Dispatch event or not
     */
    private function isExecutable($name)
    {
        if ($this->param['run'] && $this->param['event'] == $name) {
            return true;
        } elseif ($this->app['dispatcher']->hasListeners($name)) {
            if ($name == CronEvents::CRON_HOURLY && $this->nextRunTime[CronEvents::CRON_HOURLY] <= $this->runtime) {
                return true;
            } elseif (time() > $this->cronHour && $this->nextRunTime[$name] <= $this->runtime) {
                // Only run non-hourly event jobs if we've passed our cron hour today
                return true;
            }
        }

        return false;
    }

    /**
     * Get our configured hour and convert it to UNIX time.
     *
     * @return void
     */
    private function getScheduleThreshold()
    {
        $hour = $this->app['config']->get('general/cron_hour');

        if (empty($hour)) {
            $this->cronHour = strtotime('03:00');
        } elseif (is_numeric($hour)) {
            $this->cronHour = strtotime($hour . ':00');
        } elseif (is_string($hour)) {
            $this->cronHour = strtotime($hour);
        }
    }

    /**
     * If we're passed an OutputInterface, we're called from Nut and can notify
     * the end user.
     *
     * @param string $msg
     *
     * @return void
     */
    private function notify($msg)
    {
        if ($this->output !== null) {
            $this->output->writeln("<info>{$msg}</info>");
        }

        $this->app['logger.system']->info("$msg", array('event' => 'cron'));
    }

    /**
     * Set the formatted name of our table.
     *
     * @return void
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
     * Query table for next run time of each interim.
     *
     * @return void
     */
    private function getNextRunTimes()
    {
        foreach ($this->nextRunTime as $interim => $date) {
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
                $this->nextRunTime[$interim] = $this->runtime;
                $this->insert[$interim] = true;
            } else {
                $this->nextRunTime[$interim] = $this->getNextIterimRunTime($interim, $result['lastrun']);

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
     * Get the next run time for a given interim.
     *
     * @param string $interim     The interim; CRON_HOURLY, CRON_DAILY, CRON_WEEKLY, CRON_MONTHLY or CRON_YEARLY
     * @param string $lastRunTime The last execution time of the interim
     *
     * @return integer The UNIX timestamp for the interims next valid execution time
     */
    private function getNextIterimRunTime($interim, $lastRunTime)
    {
        if ($interim == CronEvents::CRON_HOURLY) {
            // For hourly we just default to the turn of the hour
            $lastCronHour  = strtotime(date('Y-m-d H', strtotime($lastRunTime)) . ':00:00');

            return strtotime("+1 hour", $lastCronHour);
        } else {
            // Get the cron time of the last run time/date
            $lastCronHour  = strtotime(date('Y-m-d', strtotime($lastRunTime)) . ' ' . $this->cronHour);

            if ($interim == CronEvents::CRON_DAILY) {
                return strtotime('+1 day', $lastCronHour);
            } elseif ($interim == CronEvents::CRON_WEEKLY) {
                return strtotime('+1 week', $lastCronHour);
            } elseif ($interim == CronEvents::CRON_MONTHLY) {
                return strtotime('+1 month', $lastCronHour);
            } elseif ($interim == CronEvents::CRON_YEARLY) {
                return strtotime('+1 year', $lastCronHour);
            }
        }

        return 0;
    }

    /**
     * Update table for last run time of each interim.
     *
     * @param string $interim
     *
     * @return void
     */
    private function setLastRun($interim)
    {
        // Define the mapping
        $map = array(
            'interim'   => $interim,
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
     * Provide feedback on exceptions in cron jobs.
     *
     * @param \Exception $e       The passed exception
     * @param string     $interim The cron handler name
     *
     * @return void
     */
    private function handleError(\Exception $e, $interim)
    {
        // Console feedback
        $this->output->writeln('<error>A ' . $interim . ' job failed. The exception returned was:</error>');
        $this->output->writeln('<error>    ' . $e->getMessage() . '</error>');
        $this->output->writeln('<error>Backtrace:</error>');
        $this->output->writeln('<error>' . $e->getTraceAsString() . '</error>');

        // Application log
        $this->app['logger.system']->error("$interim job failed: " . substr($e->getTraceAsString(), 0, 1024), array('event' => 'cron'));
    }
}
