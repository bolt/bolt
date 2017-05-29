<?php

use Bolt\Bootstrap;
use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Util\Fixtures;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Post run clean up.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CodeceptionEventsExtension extends \Codeception\Extension
{
    /** @var array list events to listen to */
    public static $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::SUITE_AFTER  => 'afterSuite',
    ];

    /**
     * Before suite callback.
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    public function beforeSuite(SuiteEvent $e)
    {
        if ($e->getSuite()->getName() === 'acceptance') {
            $this->beforeSuiteAcceptance($e);
        }
    }

    /**
     * After suite callback.
     *
     * @param \Codeception\Event\SuiteEvent $e
     */
    public function afterSuite(SuiteEvent $e)
    {
        if ($e->getSuite()->getName() === 'acceptance') {
            $this->afterSuiteAcceptance($e);
        }
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
            if (file_exists($file) && !file_exists("$file.codeception-backup")) {
                if ($keep) {
                    $this->writeln("Copying $file");
                    $fs->copy($file, "$file.codeception-backup");
                } else {
                    $this->writeln("Renaming $file");
                    $fs->rename($file, "$file.codeception-backup");
                }
            } elseif (file_exists($file)) {
                if (!$keep) {
                    $this->writeln("Removing $file");
                    $fs->remove($file);
                }
            }
        }

        // Empty the cache
        $this->nut('cache:clear');
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
        // Empty the cache
        $this->nut('cache:clear');

        $fs = new Filesystem();
        $rundir = INSTALL_ROOT . '/app/cache/codeception-run-' . time() . '/';
        $fs->mkdir($rundir);

        // Restore our backed up files, and make copies of them in app/cache/ for review
        $backups = Fixtures::get('backups');
        foreach ($backups as $file => $keep) {
            if ($fs->exists("$file.codeception-backup")) {
                $this->writeln("Restoring $file");
                $fs->copy($file, $rundir . basename($file));
                $fs->rename("$file.codeception-backup", $file, true);
            }
        }

        if ($fs->exists(INSTALL_ROOT . '/app/config/extensions/testerevents.bolt.yml')) {
            $fs->remove(INSTALL_ROOT . '/app/config/extensions/testerevents.bolt.yml');
        }
    }

    private function nut($args)
    {
        $app = Bootstrap::run(__DIR__ . '/../../..');
        $nut = $app['nut'];
        $nut->setAutoExit(false);

        $nut->run(new StringInput($args));
    }
}
