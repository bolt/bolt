<?php
namespace Bolt;

use Bolt\CronEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Event class for system compulsory cron jobs
 */
class CronEvent extends Event
{
    private $app;
    
    /**
     *
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
        echo "Clearing cache\n";
        $this->app['cache']->clearCache();
        
        // Trim log files
        echo "Trimming logs\n";
        $this->app['log']->trim();
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
}
