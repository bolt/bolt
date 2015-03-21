<?php

use Codeception\Event\FailEvent;
use Codeception\Event\PrintResultEvent;
use Codeception\Event\StepEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Post run clean up
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CodeceptionEventsExtension extends \Codeception\Platform\Extension
{
    /** @var array list events to listen to */
    public static $events = array(
        'suite.after'        => 'afterSuite',
        'test.before'        => 'beforeTest',
        'step.before'        => 'beforeStep',
        'test.fail'          => 'testFailed',
        'result.print.after' => 'printResult',
    );

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
     * Clean up after acceptance test suite run
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    private function afterSuiteAcceptance(SuiteEvent $e)
    {
        $fs = new Filesystem();

        // Sqlite DB
        if ($fs->exists(PROJECT_ROOT . '/app/database/bolt.db.codeception-backup')) {
            $this->writeln('Restoring app/database/bolt.db');
            $fs->rename(PROJECT_ROOT . '/app/database/bolt.db.codeception-backup', PROJECT_ROOT . '/app/database/bolt.db', true);
        }

        // Config files
        $configs = ['config.yml', 'contenttypes.yml', 'menu.yml', 'permissions.yml',  'routing.yml', 'taxonomy.yml'];
        foreach ($configs as $config) {
            if ($fs->exists(PROJECT_ROOT . "/app/config/$config.codeception-backup")) {
                $this->writeln("Restoring app/config/$config");
                $fs->rename(PROJECT_ROOT . "/app/config/$config.codeception-backup", PROJECT_ROOT . "/app/config/$config", true);
            }
        }

        // Events tester local extension
        if ($fs->exists(PROJECT_ROOT . '/extensions/local/bolt/tester-events/')) {
            $this->writeln('Removing extensions/local/bolt/tester-events/');
            $fs->remove(PROJECT_ROOT . '/extensions/local/bolt/tester-events/');
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
