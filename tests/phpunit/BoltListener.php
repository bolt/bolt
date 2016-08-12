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
    protected $configs = [
        'config'       => 'app/config/config.yml.dist',
        'contenttypes' => 'app/config/contenttypes.yml.dist',
        'menu'         => 'app/config/menu.yml.dist',
        'permissions'  => 'app/config/permissions.yml.dist',
        'routing'      => 'app/config/routing.yml.dist',
        'taxonomy'     => 'app/config/taxonomy.yml.dist',
    ];
    /** @var string */
    protected $theme;
    /** @var string */
    protected $boltdb;
    /** @var boolean */
    protected $timer;
    /** @var array */
    protected $tracker = [];
    /** @var string */
    protected $currentSuite;
    /** @var boolean */
    protected $reset;

    /**
     * Called on init of PHPUnit exectution.
     *
     * @see PHPUnit_Util_Configuration
     *
     * @param array   $configs Location of configuration files
     * @param bool    $theme   Location of the theme
     * @param bool    $boltDb  Location of Sqlite database
     * @param boolean $reset   Reset test environment after run
     * @param boolean $timer   Create test execution timer output
     */
    public function __construct($configs = [], $theme = false, $boltDb = false, $reset = true, $timer = true)
    {
        $this->configs = $this->getConfigs($configs);
        $this->theme = $this->getTheme($theme);
        $this->boltdb = $this->getBoltDb($boltDb);
        $this->reset = $reset;
        $this->timer = $timer;

        $this->buildTestEnv();
    }

    /**
     * Get a valid array of configuration files.
     *
     * @param array $configs
     *
     * @return array
     */
    protected function getConfigs(array $configs)
    {
        foreach ($configs as $name => $file) {
            if (empty($file)) {
                $configs[$name] = $this->getPath($name, $this->configs[$name]);
            } else {
                $configs[$name] = $this->getPath($name, $file);
            }
        }

        return $configs;
    }

    /**
     * Get the path to the theme to be used in the unit test.
     *
     * @param string $theme
     *
     * @return string
     */
    protected function getTheme($theme)
    {
        if ($theme === false || (isset($theme['theme']) && $theme['theme'] === '')) {
            return $this->getPath('theme', 'theme/base-2016');
        } else {
            return $this->getPath('theme', $theme['theme']);
        }
    }

    /**
     * Get the Bolt unit test Sqlite database.
     *
     * @param string $boltdb
     *
     * @return string
     */
    protected function getBoltDb($boltdb)
    {
        if ($boltdb === false || (isset($boltdb['boltdb']) && $boltdb['boltdb'] === '')) {
            return $this->getPath('bolt.db', 'tests/phpunit/unit/resources/db/bolt.db');
        } else {
            return $this->getPath('bolt.db', $boltdb['boltdb']);
        }
    }

    /**
     * Resolve a file path.
     *
     * @param string $name
     * @param string $file
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getPath($name, $file)
    {
        if (file_exists($file)) {
            return $file;
        }

        if (file_exists(TEST_ROOT . '/' . $file)) {
            return TEST_ROOT . '/' . $file;
        }

        if (file_exists(TEST_ROOT . '/vendor/bolt/bolt/' . $file)) {
            return TEST_ROOT . '/vendor/bolt/bolt/' . $file;
        }

        throw new \InvalidArgumentException("The file parameter '$name:' '$file' in the PHPUnit XML file is invalid.");
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
        /** @var \PHPUnit_Framework_TestCase $test */
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
        if ($fs->exists(PHPUNIT_WEBROOT)) {
            $fs->remove(PHPUNIT_WEBROOT);
        }

        // Create needed directories
        @$fs->mkdir(PHPUNIT_ROOT . '/resources/files/', 0777);
        @$fs->mkdir(PHPUNIT_WEBROOT . '/app/cache/', 0777);
        @$fs->mkdir(PHPUNIT_WEBROOT . '/app/config/', 0777);
        @$fs->mkdir(PHPUNIT_WEBROOT . '/app/database/', 0777);
        @$fs->mkdir(PHPUNIT_WEBROOT . '/extensions/', 0777);
        @$fs->mkdir(PHPUNIT_WEBROOT . '/files/', 0777);
        @$fs->mkdir(PHPUNIT_WEBROOT . '/theme/', 0777);

        // Mirror in required assets.
        $fs->mirror(TEST_ROOT . '/app/resources/',      PHPUNIT_WEBROOT . '/app/resources/',      null, ['override' => true]);
        $fs->mirror(TEST_ROOT . '/app/theme_defaults/', PHPUNIT_WEBROOT . '/app/theme_defaults/', null, ['override' => true]);
        $fs->mirror(TEST_ROOT . '/app/view/',           PHPUNIT_WEBROOT . '/app/view/',           null, ['override' => true]);

        // Make sure we wipe the db file to start with a clean one
        $fs->copy($this->boltdb, PHPUNIT_WEBROOT . '/app/database/bolt.db', true);

        // Copy in config files
        foreach ($this->configs as $config) {
            $fs->copy($config, PHPUNIT_WEBROOT . '/app/config/' . basename($config), true);
        }

        // Copy in the theme
        $name = basename($this->theme);
        $fs->mirror($this->theme, PHPUNIT_WEBROOT . '/theme/' . $name);

        // Set the theme name in config.yml
        system('php ' . NUT_PATH . ' config:set theme ' . $name);

        // Empty the cache
        system('php ' . NUT_PATH . ' cache:clear');
    }

    /**
     * Clean up after test runs
     */
    private function cleanTestEnv()
    {
        // Empty the cache
        system('php ' . NUT_PATH . ' cache:clear');

        // Remove the test database
        if ($this->reset) {
            $fs = new Filesystem();

            $fs->remove(PHPUNIT_ROOT . '/resources/files/');
            $fs->remove(PHPUNIT_WEBROOT);
        }

        // Write out a report about each test's execution time
        if ($this->timer) {
            $file = TEST_ROOT . '/app/cache/phpunit-test-timer.txt';
            if (is_readable($file)) {
                unlink($file);
            }

            // Sort the array by value, in reverse order
            arsort($this->tracker);

            foreach ($this->tracker as $test => $time) {
                $time = number_format($time, 6);
                file_put_contents($file, "$time\t\t$test\n", FILE_APPEND);
            }

            echo "\n\033[32mTest timings written out to: " . $file . "\033[0m\n\n";
        }
    }
}
