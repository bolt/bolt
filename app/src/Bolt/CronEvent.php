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
    /**
     *
     */
    function doRunJobs($interval)
    {
        switch ($interval) {
        	case CRON_HOURLY:
        	    $this->cronHourly;
        	    break;
            case CRON_DAILY:
                $this->cronDaily;
                break;
            case CRON_WEEKLY:
                $this->cronWeekly;
                break;
            case CRON_MONTHLY:
                $this->cronMonthly;
                break;
            case CRON_YEARLY:
                $this->cronYearly;
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
    }


    /**
     * Weekly jobs
     */
    private function cronWeekly()
    {
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
