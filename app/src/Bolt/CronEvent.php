<?php
namespace Bolt;

use Bolt\CronEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Event class for system compulsory cron jobs
 */
class CronEvent extends Event
{
    private $app;
    private $output;

    /**
     *
     */
    public function __construct(Application $app, $output = false)
    {
        $this->app = $app;
        $this->output = $output;
    }

    function doRunJobs($interval)
    {
        switch ($interval) {
        	case CronEvents::CRON_HOURLY:
        	    $this->cronHourly();
        	    break;
            case CronEvents::CRON_DAILY:
                $this->cronDaily();
                break;
            case CronEvents::CRON_WEEKLY:
                $this->cronWeekly();
                break;
            case CronEvents::CRON_MONTHLY:
                $this->cronMonthly();
                break;
            case CronEvents::CRON_YEARLY:
                $this->cronYearly();
                break;
        }
    }


    /**
     * Hourly jobs
     */
    private function cronHourly()
    {
    }


    /**
     * Daily jobs
     */
    private function cronDaily()
    {
        // Check for Bolt updates
    }


    /**
     * Weekly jobs
     */
    private function cronWeekly()
    {
        // Clear the cache
        $this->app['cache']->clearCache();
        $this->notify("Clearing cache");

        // Trim log files
        $this->app['log']->trim();
        $this->notify("Trimming logs");
    }


    /**
     * Monthly jobs
     */
    private function cronMonthly()
    {
    }


    /**
     * Yearly jobs
     */
    private function cronYearly()
    {
    }


    /**
     * If we're passed an OutputInterface, we're called from Nut and can notify
     * the end user
     */
    private function notify($msg)
    {
        if($this->output !== false) {
            $this->output->writeln("<info>    {$msg}</info>");
        }
    }
}
