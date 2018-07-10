<?php

namespace Bolt\Tests;

use Bolt\Application;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Test as Test;
use PHPUnit_Framework_TestSuite as TestSuite;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PHPUnit listener class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltListener extends BaseTestListener
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
    /** @var bool */
    protected $timer;
    /** @var array */
    protected $tracker = [];
    /** @var string[] */
    protected $currentSuite = [];
    /** @var bool */
    protected $reset;

    /**
     * Called on init of PHPUnit exectution.
     *
     * @see \PHPUnit_Util_Configuration
     *
     * @param array $configs Location of configuration files
     * @param bool  $theme   Location of the theme
     * @param bool  $boltDb  Location of Sqlite database
     * @param bool  $reset   Reset test environment after run
     * @param bool  $timer   Create test execution timer output
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
     * @param array|bool $theme
     *
     * @return string
     */
    protected function getTheme($theme)
    {
        if ($theme === false || (isset($theme['theme']) && $theme['theme'] === '')) {
            return $this->getPath('theme', 'theme/base-2018');
        }

        return $this->getPath('theme', $theme['theme']);
    }

    /**
     * Get the Bolt unit test Sqlite database.
     *
     * @param array|bool $boltdb
     *
     * @return string
     */
    protected function getBoltDb($boltdb)
    {
        if ($boltdb === false || (isset($boltdb['boltdb']) && $boltdb['boltdb'] === '')) {
            return $this->getPath('bolt.db', 'tests/phpunit/unit/resources/db/bolt.db');
        }

        return $this->getPath('bolt.db', $boltdb['boltdb']);
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
     * {@inheritdoc}
     */
    public function endTest(Test $test, $time)
    {
        /** @var TestCase $test */
        $name = $test->getName();
        $suite = end($this->currentSuite);
        $this->tracker[$suite . '::' . $name] = $time;
    }

    /**
     * {@inheritdoc}
     */
    public function startTestSuite(TestSuite $suite)
    {
        array_push($this->currentSuite, $suite->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function endTestSuite(TestSuite $suite)
    {
        array_pop($this->currentSuite);
    }

    /**
     * Build the pre-requisites for our test environment.
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
        $fs->mirror(TEST_ROOT . '/app/view/twig',       PHPUNIT_WEBROOT . '/app/view/twig',       null, ['override' => true]);

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
        $this->nut("config:set theme $name --quiet");
    }

    /**
     * Clean up after test runs.
     */
    private function cleanTestEnv()
    {
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

    private function nut($command)
    {
        $app = new Application([
            'path_resolver.root'  => PHPUNIT_WEBROOT,
            'path_resolver.paths' => [
                'web' => '.',
            ],
        ]);
        $nut = $app['nut'];
        $nut->setAutoExit(false);

        $result = $nut->run(new StringInput($command));
        if ($result && strpos($command, '-q') !== false) {
            throw new \RuntimeException(sprintf('[FAILED] %s', $command));
        }
    }
}
