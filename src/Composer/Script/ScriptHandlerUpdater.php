<?php

namespace Bolt\Composer\Script;

use Bolt\Composer\ScriptHandler;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Adds handler to composer.json scripts section or shows how (on error).
 *
 * @internal
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class ScriptHandlerUpdater
{
    /** @var Event */
    private $event;
    /** @var IOInterface */
    private $io;

    /** @var array|null */
    private $scripts;

    /**
     * Constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
        $this->io = $event->getIO();
    }

    /**
     * Checks if composer.json scripts already have the handler we are adding.
     *
     * @return bool
     */
    public function needsUpdate()
    {
        return !$this->hasScript(ScriptEvents::POST_UPDATE_CMD, ScriptHandler::class . '::updateProject');
    }

    /**
     * Attempt to update composer.json, or show how if we can't update the file.
     */
    public function update()
    {
        try {
            $this->updateAutomatically();
        } catch (\Exception $e) {
            $this->showHow();
        }
    }

    /**
     * Update composer.json file.
     */
    private function updateAutomatically()
    {
        $source = $this->event->getComposer()->getConfig()->getConfigSource();
        $file = new JsonFile($source->getName(), null, $this->io);
        $contents = $file->read();

        $this->scripts = $contents['scripts'] = $this->modifyScripts($contents['scripts']);

        $file->write($contents);

        $this->io->writeError([
            '<info>',
            'Bolt has a new separate composer script handler to help with updates!',
            "We've automatically added this to your composer.json!",
            '</info>',
        ]);
    }

    /**
     * Tell users of the change and show how the composer.json file should be updated.
     */
    private function showHow()
    {
        if (!$this->scripts) {
            $scripts = $this->event->getComposer()->getPackage()->getScripts();
            $scripts = $this->modifyScripts($scripts);
        } else {
            $scripts = $this->scripts;
        }
        $scripts = substr(JsonFile::encode(['scripts' => $scripts]), 1, -1);
        $cls = str_replace('\\', '\\\\', ScriptHandler::class);
        $name = "\"$cls::updateProject\",";
        $scripts = str_replace($name, "<info>$name</info>", $scripts);

        $this->io->writeError([
            '',
            '<info>Bolt has a new separate composer script handler to help with updates!</info>',
            '<warning>Unfortunately, we could not update your composer.json file automatically.',
            'Please update the "scripts" section of your composer.json file to this:</warning>',
            $scripts,
            '',
        ]);
    }

    /**
     * Add updateProject handler right before installAssets handler.
     *
     * @param string[] $scripts
     *
     * @return string[]
     */
    private function modifyScripts($scripts)
    {
        $updateScripts = (array) $scripts[ScriptEvents::POST_UPDATE_CMD];

        $index = array_search(ScriptHandler::class . '::installAssets', $updateScripts) ?: 0;
        array_splice($updateScripts, $index, 1, [ScriptHandler::class . '::updateProject', ScriptHandler::class . '::installAssets']);

        $scripts[ScriptEvents::POST_UPDATE_CMD] = $updateScripts;

        return $scripts;
    }

    /**
     * @param string $eventName
     * @param string $script
     *
     * @return bool
     */
    private function hasScript($eventName, $script)
    {
        $scripts = $this->event->getComposer()->getPackage()->getScripts();
        if (!isset($scripts[$eventName])) {
            return false;
        }

        return in_array($script, $scripts[$eventName]);
    }
}
