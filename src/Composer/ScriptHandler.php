<?php

namespace Bolt\Composer;

use Bolt\Composer\Script\BootstrapYamlUpdater;
use Bolt\Composer\Script\BundleConfigurator;
use Bolt\Composer\Script\DirectoryConfigurator;
use Bolt\Composer\Script\DirectorySyncer;
use Bolt\Composer\Script\NewStableVersionNotifier;
use Bolt\Composer\Script\ScriptHandlerUpdater;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer event script handler.
 *
 * @internal
 */
final class ScriptHandler
{
    /**
     * Install Bolt's assets.
     *
     * This should be ran on "post-install-cmd" and "post-update-cmd" events.
     *
     * @param Event $event
     * @param bool  $checkForCreateProject
     */
    public static function installAssets(Event $event, $checkForCreateProject = true)
    {
        /*
         * Ugly hack to prevent application from being booted before configureProject can be called.
         */
        global $argv;
        if ($checkForCreateProject && strpos(implode(' ', $argv), 'create-project') > 0) {
            return;
        }

        static::runUpdateProjectFromAssets($event);

        $syncer = DirectorySyncer::fromEvent($event);
        $syncer->sync('bolt_assets', 'bolt_assets', true, ['css', 'fonts', 'img', 'js']);

        NewStableVersionNotifier::run();
    }

    /**
     * Install Bolt's default themes and files.
     *
     * This should be ran on "post-create-project-cmd" event.
     *
     * @param Event $event
     */
    public static function installThemesAndFiles(Event $event)
    {
        $syncer = DirectorySyncer::fromEvent($event);

        $syncer->sync('files', 'files');
        $syncer->sync('%vendor%/bolt/themes', 'themes', true, ['base-2016', 'base-2018', 'skeleton']);
    }

    /**
     * Updates project existing structure if needed.
     *
     * @param Event $event
     */
    public static function updateProject(Event $event)
    {
        BootstrapYamlUpdater::fromEvent($event)->update();
    }

    /**
     * Configures installation's directory structure and default site bundle.
     *
     * The configured paths & extensions are written to .bolt.yml
     * and the skeleton structure is modified accordingly.
     *
     * @param Event $event
     */
    public static function configureProject(Event $event)
    {
        DirectoryConfigurator::fromEvent($event)->run();
        BundleConfigurator::fromEvent($event)->run();
        NewStableVersionNotifier::run();

        // Install assets here since they they were skipped above
        static::installAssets($event, false);
    }

    /**
     * Checks if updateProject is in composer.json. If not,
     * this adds it / shows how to add it, and then runs updateProject.
     *
     * @param Event $event
     */
    private static function runUpdateProjectFromAssets(Event $event)
    {
        if ($event->getName() !== ScriptEvents::POST_UPDATE_CMD) {
            return;
        }

        $updater = new ScriptHandlerUpdater($event);

        if (!$updater->needsUpdate()) {
            return;
        }
        $updater->update();

        static::updateProject($event);
    }
}
