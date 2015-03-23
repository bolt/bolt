<?php

namespace Bolt\Tests;

/**
 * PHPUnit listener class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltListener implements \PHPUnit_Framework_TestListener
{
    /** @var array */
    protected $tracker;

    /** @var boolean */
    protected $timer;

    /** @var boolean */
    protected $reset;

    /**
     * Called on init of PHPUnit exectution.
     *
     * @see PHPUnit_Util_Configuration
     *
     * @param boolean $timer Create test execution timer output
     * @param boolean $reset Reset test environment after run
     */
    public function __construct($timer, $reset)
    {
        $this->timer = $timer;
        $this->reset = $reset;

        $this->buildTestEnv();
    }

    /**
     * Destructor that will be called at the completion of the PHPUnit execution.
     *
     * Add code here to clean up our test environment.
     */
    public function __destruct()
    {
        $this->cleanTestEnv();
    }

    /**
     * An error occurred.
     *
     * @see PHPUnit_Framework_TestListener::addError()
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * A failure occurred.
     *
     * @see PHPUnit_Framework_TestListener::addFailure()
     *
     * @param \PHPUnit_Framework_Test                 $test
     * @param \PHPUnit_Framework_AssertionFailedError $e
     * @param float                                   $time
     */
    public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time)
    {
    }

    /**
     * A test was incomplete.
     *
     * @see PHPUnit_Framework_TestListener::addIncompleteTest()
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * A test  is deemed risky.
     *
     * @see PHPUnit_Framework_TestListener::addRiskyTest()
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addRiskyTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * Test has been skipped.
     *
     * @see PHPUnit_Framework_TestListener::addSkippedTest()
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception              $e
     * @param float                   $time
     */
    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
    }

    /**
     * A test started.
     *
     * @see PHPUnit_Framework_TestListener::startTest()
     *
     * @param \PHPUnit_Framework_Test $test
     */
    public function startTest(\PHPUnit_Framework_Test $test)
    {
    }

    /**
     * A test ended.
     *
     * @see PHPUnit_Framework_TestListener::endTest()
     *
     * @param \PHPUnit_Framework_Test $test
     * @param float                   $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        $name = $test->getName();
        $this->tracker[$name] = $time;
    }

    /**
     * A test suite started.
     *
     * @see PHPUnit_Framework_TestListener::startTestSuite()
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
    }

    /**
     * A test suite ended.
     *
     * @see PHPUnit_Framework_TestListener::endTestSuite()
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
    }

    /**
     * Build the pre-requisites for our test environment
     */
    private function buildTestEnv()
    {
        // Make sure we wipe the db file to start with a clean one
        if (is_readable(TEST_ROOT . '/bolt.db')) {
            unlink(TEST_ROOT . '/bolt.db');
        }
        copy(PHPUNIT_ROOT . '/resources/db/bolt.db', TEST_ROOT . '/bolt.db');

        @mkdir(TEST_ROOT . '/app/cache/', 0777, true);
    }

    /**
     * Clean up after test runs
     */
    private function cleanTestEnv()
    {
        // Write out a report about each test's execution time
        if ($this->timer) {
            file_put_contents(TEST_ROOT . '/app/cache/unit-test-timer.txt', print_r($this->tracker, true));
        }

        if ($this->reset) {
            if (is_readable(TEST_ROOT . '/bolt.db')) {
                unlink(TEST_ROOT . '/bolt.db');
            }
        }
    }
}
