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
        $this->runtime = date("Y-m-d H:i:s", time());
        $this->next_run_time = array('hourly' => 0, 'daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0);
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
        if ($this->isExecutable(CronEvents::CRON_HOURLY)) {
            $this->notify("Running Cron Hourly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_HOURLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_HOURLY);
            }

            $this->setLastRun('hourly');
        }

        if ($this->isExecutable(CronEvents::CRON_DAILY)) {
            $this->notify("Running Cron Daily Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_DAILY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_DAILY);
            }

            $this->setLastRun('daily');
        }

        if ($this->isExecutable(CronEvents::CRON_WEEKLY)) {
            $this->notify("Running Cron Weekly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_WEEKLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_WEEKLY);
            }

            $this->setLastRun('weekly');
        }

        if ($this->isExecutable(CronEvents::CRON_MONTHLY)) {
            $this->notify("Running Cron Monthly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_MONTHLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_MONTHLY);
            }

            $this->setLastRun('monthly');
        }

        if ($this->isExecutable(CronEvents::CRON_YEARLY)) {
            $this->notify("Running Cron Yearly Jobs");

            try {
                $this->app['dispatcher']->dispatch(CronEvents::CRON_YEARLY, $event);
            } catch (\Exception $e) {
                $this->handleError($e, CronEvents::CRON_YEARLY);
            }

            $this->setLastRun('yearly');
        }
    }

    /**
     * Test whether or not to call dispatcher
     *
     * @param string $name The cron event name
     * @return boolean True  - Dispatch event
     *                 False - Passover event
     */
    private function isExecutable($name)
    {
        if ($this->param['run'] && $this->param['event'] == $name) {
            return true;
        } elseif ($this->app['dispatcher']->hasListeners($name)) {
            if ($name == CronEvents::CRON_HOURLY && $this->next_run_time['hourly'] < strtotime("-1 hour")) {
                return true;
            } elseif (time() > $this->cron_hour) {
                // Only check the running of these if we've passed our cron hour today
                if ($name == CronEvents::CRON_DAILY && $this->next_run_time['daily'] < strtotime("-1 day")) {
                    return true;
                } elseif ($name == CronEvents::CRON_WEEKLY && $this->next_run_time['weekly'] < strtotime("-1 week")) {
                    return true;
                } elseif ($name == CronEvents::CRON_MONTHLY && $this->next_run_time['monthly'] < strtotime("-1 month")) {
                    return true;
                } elseif ($name == CronEvents::CRON_YEARLY && $this->next_run_time['yearly'] < strtotime("-1 year")) {
                    return true;
                }
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

        if ($prefix[strlen($this->prefix) - 1] != "_") {
            $prefix .= "_";
        }

        $this->tablename = $prefix . "cron";
    }

    /**
     * Query table for last run time of each interim
     */
    private function getLastRun()
    {
        foreach ($this->next_run_time as $interim => $date) {
            $query =
                "SELECT lastrun " .
                "FROM {$this->tablename} " .
                "WHERE interim = :interim " .
                "ORDER BY lastrun DESC";

            $result = $this->app['db']->fetchAssoc($query, array('interim' => $interim));

            // If we get an empty result for the interim, set it to the current
            // run time and notify the update method to do an INSERT rather than
            // an UPDATE.
            if (empty($result)) {
                $this->insert[$interim] = true;
            } else {
                $this->next_run_time[$interim] = strtotime($result['lastrun']);
                $this->insert[$interim] = false;
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
            'lastrun'   => $this->runtime,
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
