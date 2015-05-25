<?php

namespace Bolt\Tests;

use Symfony\Component\Filesystem\Filesystem;

/**
 * PHPUnit listener class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltListener implements \PHPUnit_Framework_TestListener
{
    /** @var array */
    protected $tracker = [];

    /** @var boolean */
    protected $timer;

    /** @var boolean */
    protected $reset;

    /** @var boolean */
    protected $theme;

    /** @var string */
    protected $path;

    /** @var string */
    protected $currentSuite;

    /**
     * Called on init of PHPUnit exectution.
     *
     * @see PHPUnit_Util_Configuration
     *
     * @param boolean $timer Create test execution timer output
     * @param boolean $reset Reset test environment after run
     * @param boolean $theme Copy in theme directory
     * @param string  $path  Relative path to a theme to import
     */
    public function __construct($timer, $reset, $theme, $path)
    {
        $this->timer = $timer;
        $this->reset = $reset;
        $this->theme = $theme;
        $this->path  = $path;

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
        $this->tracker[$this->currentSuite . '::' . $name] = $time;
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
        $this->currentSuite = $suite->getName();
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
        unset($this->currentSuite);
    }

    /**
     * Build the pre-requisites for our test environment
     */
    private function buildTestEnv()
    {
        $fs = new Filesystem();

        // Make sure we wipe the db file to start with a clean one
        $fs->copy(PHPUNIT_ROOT . '/resources/db/bolt.db', TEST_ROOT . '/bolt.db', true);

        // Create needed directories
        @$fs->mkdir(TEST_ROOT . '/app/cache/', 0777);
        @$fs->mkdir(PHPUNIT_ROOT . '/resources/files/', 0777);

        // If enabled, copy in the requested theme
        if ($this->theme) {
            @$fs->mkdir(TEST_ROOT . '/theme/', 0777);

            $name = basename($this->path);
            $fs->mirror(realpath(TEST_ROOT . '/' . $this->path), TEST_ROOT . '/theme/' . $name);

            // Set the theme name in config.yml
            system('php ' . NUT_PATH . ' config:set theme ' . $name);
        }

        // Empty the cache
        system('php ' . NUT_PATH . ' cache:clear');
    }

    /**
     * Clean up after test runs
     */
    private function cleanTestEnv()
    {
        // Remove the test database
        if ($this->reset) {
            $fs = new Filesystem();

            $fs->remove(TEST_ROOT . '/bolt.db');
            $fs->remove(PHPUNIT_ROOT . '/resources/files/');

            // If enabled, remove the requested theme
            if ($this->theme) {
                $name = basename($this->path);
                $fs->remove(TEST_ROOT . '/theme/' . $name);
            }
        }

        // Empty the cache
        system('php ' . NUT_PATH . ' cache:clear');

        // Write out a report about each test's execution time
        if ($this->timer) {
            $file = TEST_ROOT . '/app/cache/phpunit-test-timer.txt';
            if (is_readable($file)) {
                unlink($file);
            }

            arsort($this->tracker);
            foreach ($this->tracker as $test => $time) {
                $time = substr($time, 0, 6);
                file_put_contents($file, "$time\t\t$test\n", FILE_APPEND);
            }
        }
    }
}
