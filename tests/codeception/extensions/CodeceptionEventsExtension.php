<?php

use Codeception\Event\FailEvent;
use Codeception\Event\PrintResultEvent;
use Codeception\Event\StepEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Util\Fixtures;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Post run clean up
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CodeceptionEventsExtension extends \Codeception\Platform\Extension
{
    /** @var array list events to listen to */
    public static $events = [
        'suite.before'       => 'beforeSuite',
        'suite.after'        => 'afterSuite',
        'test.before'        => 'beforeTest',
        'step.before'        => 'beforeStep',
        'test.fail'          => 'testFailed',
        'result.print.after' => 'printResult',
    ];

    /**
     * Before suite callback
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    public function beforeSuite(SuiteEvent $e)
    {
        /** @var $suite \PHPUnit_Framework_TestSuite */
        $suite = $e->getSuite();

        if ($suite->getName() === 'acceptance') {
            $this->beforeSuiteAcceptance($e);
        }

        if ($suite->getName() === 'functional') {
            $this->beforeSuiteFunctional($e);
        }

        if ($suite->getName() === 'unit') {
            $this->beforeSuiteUnit($e);
        }
    }

    /**
     * After suite callback
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    public function afterSuite(SuiteEvent $e)
    {
        /** @var $suite \PHPUnit_Framework_TestSuite */
        $suite = $e->getSuite();

        if ($suite->getName() === 'acceptance') {
            $this->afterSuiteAcceptance($e);
        }

        if ($suite->getName() === 'functional') {
            $this->afterSuiteFunctional($e);
        }

        if ($suite->getName() === 'unit') {
            $this->afterSuiteUnit($e);
        }
    }

    /**
     * Before individual test callback
     *
     * @param \Codeception\Event\TestEvent $e
     */
    public function beforeTest(TestEvent $e)
    {
    }

    /**
     * Before test step callback
     *
     * @param \Codeception\Event\StepEvent $e
     */
    public function beforeStep(StepEvent $e)
    {
    }

    /**
     * Test failure callback
     *
     * @param \Codeception\Event\FailEvent $e
     */
    public function testFailed(FailEvent $e)
    {
    }

    /**
     * Priting the test results callback
     *
     * @param \Codeception\Event\PrintResultEvent $e
     */
    public function printResult(PrintResultEvent $e)
    {
    }

    /**
     * Set up before acceptance test suite run.
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function beforeSuiteAcceptance(SuiteEvent $e)
    {
        // Get the Filesystem object
        $fs = new Filesystem();

        // Back up files that we'll modify during tests
        $backups = Fixtures::get('backups');
        foreach ($backups as $file => $keep) {
            if (file_exists(PROJECT_ROOT . $file) && !file_exists(PROJECT_ROOT . $file . '.codeception-backup')) {
                if ($keep) {
                    $this->writeln("Copying $file");
                    $fs->copy(PROJECT_ROOT . $file, PROJECT_ROOT. $file . '.codeception-backup');
                } else {
                    $this->writeln("Renaming $file");
                    $fs->rename(PROJECT_ROOT . $file, PROJECT_ROOT. $file . '.codeception-backup');
                }
            } elseif (file_exists(PROJECT_ROOT . $file)) {
                if (!$keep) {
                    $this->writeln("Removing $file");
                    $fs->remove(PROJECT_ROOT . $file);
                }
            }
        }

        // Install the local extension
        $this->writeln("Installing local extension");
        $fs->mirror(CODECEPTION_DATA . '/extensions/local/', PROJECT_ROOT . '/extensions/local/', null, array('override' => true, 'delete' => true));

        // Empty the cache
        system('php ' . NUT_PATH . ' cache:clear');
    }

    /**
     * Set up before functional test suite run
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function beforeSuiteFunctional(SuiteEvent $e)
    {
        //
    }

    /**
     * Set up before unit test suite run
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function beforeSuiteUnit(SuiteEvent $e)
    {
        //
    }

    /**
     * Clean up after acceptance test suite run.
     *
     * We will copy the configs and database used to cache for inspection, really
     * only useful on test development runs but little impact overall.
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function afterSuiteAcceptance(SuiteEvent $e)
    {
        $fs = new Filesystem();
        $rundir = PROJECT_ROOT . '/app/cache/codeception-run-' . time() . '/';
        $fs->mkdir($rundir);

        // Restore our backed up files, and make copies of them in app/cache/ for review
        $backups = Fixtures::get('backups');
        foreach ($backups as $file => $keep) {
            if ($fs->exists(PROJECT_ROOT . $file . '.codeception-backup')) {
                $this->writeln("Restoring $file");
                $fs->copy(PROJECT_ROOT . $file, $rundir . basename($file));
                $fs->rename(PROJECT_ROOT . $file . '.codeception-backup', PROJECT_ROOT . $file, true);
            }
        }
    }

    /**
     * Clean up after functional test suite run
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function afterSuiteFunctional(SuiteEvent $e)
    {
        //
    }

    /**
     * Clean up after unit test suite run
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function afterSuiteUnit(SuiteEvent $e)
    {
        //
    }
}
