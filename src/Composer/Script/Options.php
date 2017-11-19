<?php

namespace Bolt\Composer\Script;

use Composer\Script\Event;

/**
 * Handles options in composer's extra section and env vars.
 *
 * @internal
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class Options
{
    /** @var array */
    private $composerExtra;

    /**
     * Constructor.
     *
     * @param array $composerExtra
     */
    public function __construct(array $composerExtra = [])
    {
        $this->composerExtra = $composerExtra;
    }

    /**
     * Create from a Composer event object.
     *
     * @param Event $event
     *
     * @return Options
     */
    public static function fromEvent(Event $event)
    {
        return new static($event->getComposer()->getPackage()->getExtra());
    }

    /**
     * Returns the directory mode.
     *
     * @return int
     */
    public function getDirMode()
    {
        $dirMode = $this->get('dir-mode', 0777);
        $dirMode = is_string($dirMode) ? octdec($dirMode) : $dirMode;

        return $dirMode;
    }

    /**
     * Get an option from environment variable or composer's extra section.
     *
     * Example: With key "dir-mode" it checks for "BOLT_DIR_MODE" environment variable,
     * then "bolt-dir-mode" in composer's extra section, then returns given default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($value = $this->getEnv($key)) {
            return $value;
        }

        $key = strtolower(str_replace('_', '-', $key));

        if (strpos($key, 'bolt-') !== 0) {
            $key = 'bolt-' . $key;
        }

        return isset($this->composerExtra[$key]) ? $this->composerExtra[$key] : $default;
    }

    /**
     * @param string $key
     *
     * @return array|false|string
     */
    private function getEnv($key)
    {
        $key = strtoupper(str_replace('-', '_', $key));

        if (strpos($key, 'BOLT_') !== 0) {
            $key = 'BOLT_' . $key;
        }

        return getenv($key);
    }
}
