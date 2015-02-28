<?php
namespace Bolt\Events;

use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Event class for system compulsory cron jobs.
 */
class CronEvent extends Event
{
    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    public $output;

    public function __construct(Application $app, OutputInterface $output = null)
    {
        $this->app = $app;
        $this->output = $output;

        // Add listeners
        $this->app['dispatcher']->addListener(CronEvents::CRON_HOURLY, array($this, 'doRunScheduledJobs'));
        $this->app['dispatcher']->addListener(CronEvents::CRON_DAILY, array($this, 'doRunScheduledJobs'));
        $this->app['dispatcher']->addListener(CronEvents::CRON_WEEKLY, array($this, 'doRunScheduledJobs'));
        $this->app['dispatcher']->addListener(CronEvents::CRON_MONTHLY, array($this, 'doRunScheduledJobs'));
        $this->app['dispatcher']->addListener(CronEvents::CRON_YEARLY, array($this, 'doRunScheduledJobs'));
    }

    public function doRunScheduledJobs(Event $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        switch ($eventName) {
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
     * Hourly jobs.
     */
    private function cronHourly()
    {
    }

    /**
     * Daily jobs.
     */
    private function cronDaily()
    {
        // Check for Bolt updates
    }

    /**
     * Weekly jobs.
     */
    private function cronWeekly()
    {
        // Clear the cache
        $this->app['cache']->clearCache();
        $this->notify("Clearing cache");

        // Trim system log files
        $this->app['logger.manager']->trim('system');

        // Trim change log files
        $this->app['logger.manager']->trim('change');

        $this->notify("Trimming logs");
    }

    /**
     * Monthly jobs.
     */
    private function cronMonthly()
    {
    }

    /**
     * Yearly jobs.
     */
    private function cronYearly()
    {
    }

    /**
     * If we're passed an OutputInterface, we're called from Nut and can notify
     * the end user.
     *
     * @param string $msg
     */
    private function notify($msg)
    {
        if ($this->output !== null) {
            $this->output->writeln("<comment>    {$msg}</comment>");
        }
    }
}
