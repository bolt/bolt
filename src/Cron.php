<?php

namespace Bolt;

use Bolt\Events\CronEvent;
use Bolt\Events\CronEvents;
use Bolt\Storage\Entity;
use Silex;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Simple cron dispatch class for Bolt.
 *
 * To create a listener you need to something similar in your class:
 *      use Bolt\Events\CronEvents;
 *      $this->app['dispatcher']->addListener(CronEvents::CRON_INTERVAL, [$this, 'myJobCallbackMethod']);
 *
 * CRON_INTERVAL should be replace with one of the following:
 *      * CRON_HOURLY
 *      * CRON_DAILY
 *      * CRON_WEEKLY
 *      * CRON_MONTHLY
 *      * CRON_YEARLY
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Cron extends Event
{
    /** @var array */
    public $lastruns = [];

    /** @var \Bolt\Storage\Repository\CronRepository */
    protected $repository;

    /** @var \Silex\Application */
    private $app;
    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;
    /** @var array Passed in console paramters. */
    private $param;
    /** @var \DateTime The start of the execution time for this cron instance.*/
    private $runtime;
    /** @var \DateTime */
    private $cronHour;
    /** @var array The next elegible run time for each interim. */
    private $jobs = [
        CronEvents::CRON_HOURLY  => ['increment' => 'PT1H', 'message' => 'Running Cron Hourly Jobs'],
        CronEvents::CRON_DAILY   => ['increment' => 'P1D',  'message' => 'Running Cron Daily Jobs'],
        CronEvents::CRON_WEEKLY  => ['increment' => 'P1W',  'message' => 'Running Cron Weekly Jobs'],
        CronEvents::CRON_MONTHLY => ['increment' => 'P1M',  'message' => 'Running Cron Monthly Jobs'],
        CronEvents::CRON_YEARLY  => ['increment' => 'P1Y',  'message' => 'Running Cron Yearly Jobs'],
    ];

    /**
     * Constructor.
     *
     * @param Silex\Application $app
     * @param OutputInterface   $output
     */
    public function __construct(Silex\Application $app, OutputInterface $output = null)
    {
        $this->app = $app;
        $this->output = $output;
        $this->repository = $this->app['storage']->getRepository('Bolt\Storage\Entity\Cron');
    }

    /**
     * Run the jobs.
     *
     * @param array $param
     *
     * @return boolean
     */
    public function execute($param = [])
    {
        $this->param = $param;
        $this->runtime = new \DateTime();

        // Get schedules
        $this->getRunTimes();

        // Time of day for daily, weekly, monthly and yearly jobs
        $this->getScheduleThreshold();

        $event = new CronEvent($this->app, $this->output);

        // Process event listeners
        foreach ($this->jobs as $name => $data) {
            $this->executeSingle($event, $name, $data);
        }

        return true;
    }

    /**
     * Run a single cron dispatcher.
     *
     * @param CronEvent $event
     * @param string    $interimName
     * @param array     $data
     */
    private function executeSingle(CronEvent $event, $interimName, array $data)
    {
        if ($this->isExecutable($interimName)) {
            $this->notify($data['message']);

            try {
                $this->app['dispatcher']->dispatch($interimName, $event);
                $this->jobs[$interimName]['entity']->setLastrun($this->runtime);
                $this->repository->save($this->jobs[$interimName]['entity']);
            } catch (\Exception $e) {
                $this->handleError($e, $interimName);
            }
        }
    }

    /**
     * Test whether or not to call dispatcher.
     *
     * @param string $interimName The cron event name
     *
     * @return boolean Dispatch event or not
     */
    private function isExecutable($interimName)
    {
        if ($this->param['run'] && $this->param['event'] === $interimName) {
            return true;
        } elseif (!$this->app['dispatcher']->hasListeners($interimName)) {
            return false;
        }

        $nextRun = $this->jobs[$interimName]['nextRunTime'];
        if ($interimName === CronEvents::CRON_HOURLY && $this->runtime >= $nextRun) {
            return true;
        } elseif ($this->runtime > $this->cronHour && $this->runtime >= $nextRun) {
            // Only run non-hourly event jobs if we've passed our cron hour today
            return true;
        }

        return false;
    }

    /**
     * Get our configured hour and convert it to UNIX time.
     */
    private function getScheduleThreshold()
    {
        $hour = $this->app['config']->get('general/cron_hour', '03:00');

        if (is_numeric($hour)) {
            $hour = $hour . ':00';
        }

        $this->cronHour = new \DateTime($hour);
    }

    /**
     * Query table for last run time of each interim.
     */
    private function getRunTimes()
    {
        foreach (array_keys($this->jobs) as $interimName) {
            if (!$runEntity = $this->repository->getNextRunTime($interimName)) {
                $epoch = new \DateTime();
                $epoch->setTimestamp(0);
                $runEntity = new Entity\Cron(['interim' => $interimName, 'lastrun' => $epoch]);
            }

            $this->jobs[$interimName]['entity'] = $runEntity;
            $this->jobs[$interimName]['lastRunTime'] = $runEntity->getLastrun();
            $this->jobs[$interimName]['nextRunTime'] = $this->getNextRunTime($interimName, $runEntity);
        }
    }

    /**
     * Get the next run time for a given interim.
     *
     * @param string                    $interimName The interim; CRON_HOURLY, CRON_DAILY, CRON_WEEKLY, CRON_MONTHLY or CRON_YEARLY
     * @param \Bolt\Storage\Entity\Cron $runEntity   The last execution time of the interim
     *
     * @return integer The UNIX timestamp for the interims next valid execution time
     */
    private function getNextRunTime($interimName, Entity\Cron $runEntity)
    {
        $interval = new \DateInterval($this->jobs[$interimName]['increment']);
        $nextRunTime = clone $runEntity->getLastrun();
        $nextRunTime->add($interval);

        return $nextRunTime;
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
            $this->output->writeln("<info>{$msg}</info>");
        }

        $this->app['logger.system']->info("$msg", ['event' => 'cron']);
    }

    /**
     * Provide feedback on exceptions in cron jobs.
     *
     * @param \Exception $e           The passed exception
     * @param string     $interimName The cron handler name
     */
    private function handleError(\Exception $e, $interimName)
    {
        // Console feedback
        $this->output->writeln('<error>A ' . $interimName . ' job failed. The exception returned was:</error>');
        $this->output->writeln('<error>    ' . $e->getMessage() . '</error>');
        $this->output->writeln('<error>Backtrace:</error>');
        $this->output->writeln('<error>' . $e->getTraceAsString() . '</error>');

        // Application log
        $this->app['logger.system']->error("$interimName job failed: " . substr($e->getTraceAsString(), 0, 1024), ['event' => 'cron']);
    }
}
