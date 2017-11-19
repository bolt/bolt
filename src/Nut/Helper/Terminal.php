<?php

namespace Bolt\Nut\Helper;

/**
 * An easy way to get the terminal's width/height.
 *
 * Values are not cached here for accuracy, so limited calls to this class is recommended.
 *
 * This started from Symfony's code, and modified to be its own class and to use a signal handler if available.
 *
 * Symfony 3.x does have their own Terminal class, but values are cached there. If the user resizes their terminal
 * window the code will never know about it.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class Terminal
{
    private static $width;
    private static $height;
    private static $pcntlEnabled;
    private static $pcntlAsync;
    private static $pcntlNeedsUpdate = true;

    /**
     * Returns the Terminal's width.
     *
     * @return int
     */
    public static function getWidth()
    {
        $width = getenv('COLUMNS');
        if ($width !== false) {
            return (int) trim($width);
        }

        static::updateDimensions();

        return static::$width;
    }

    /**
     * Returns the Terminal's height.
     *
     * @return int
     */
    public static function getHeight()
    {
        $height = getenv('LINES');
        if ($height !== false) {
            return (int) trim($height);
        }

        static::updateDimensions();

        return static::$height;
    }

    /**
     * Update our dimension properties.
     */
    private static function updateDimensions()
    {
        if (!static::checkPcntl()) {
            return;
        }

        static::fetchDimensions();
    }

    /**
     * Initializes and checks if we have received a SIGWINCH signal.
     *
     * @return bool whether dimensions should be fetched
     */
    private static function checkPcntl()
    {
        if (static::$pcntlEnabled === null) {
            if (!extension_loaded('pcntl')) {
                static::$pcntlEnabled = false;

                return true;
            }
            static::$pcntlEnabled = true;

            if (version_compare(PHP_VERSION, '7.1', '>=')) {
                pcntl_async_signals(true);
                static::$pcntlAsync = true;
            }

            // This can be dispatched multiple times when resizing,
            // so it's important that we throttle fetching dimensions.
            pcntl_signal(SIGWINCH, function () {
                static::$pcntlNeedsUpdate = true;
            });
        } elseif (static::$pcntlEnabled === false) {
            return true;
        }

        if (!static::$pcntlAsync) {
            pcntl_signal_dispatch();
        }

        return static::$pcntlNeedsUpdate;
    }

    /**
     * Fetch dimensions based on platform.
     */
    private static function fetchDimensions()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $dimensions = static::getAnsiCon();
            if (!$dimensions) {
                $dimensions = static::getConsoleMode();
            }
        } else {
            $dimensions = static::getSttyColumns();
        }

        // Default, if above methods fail.
        $dimensions += [80, 50];

        static::$width = $dimensions[0];
        static::$height = $dimensions[1];

        // Reset signal handler flag.
        static::$pcntlNeedsUpdate = false;
    }

    /**
     * Parse width & height from ANSICON env var.
     *
     * @return int[]
     */
    private static function getAnsiCon()
    {
        if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
            // extract [w, H] from "wxh (WxH)"
            // or [w, h] from "wxh"
            return [
                (int) $matches[1],
                isset($matches[4]) ? (int) $matches[4] : (int) $matches[2],
            ];
        }

        return [];
    }

    /**
     * Run console mode command and parse output to width & height.
     *
     * @return int[]
     */
    private static function getConsoleMode()
    {
        $info = static::runCommand('mode CON');

        if ($info && preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
            // extract [w, h] from "wxh"
            return [(int) $matches[2], (int) $matches[1]];
        }

        return [];
    }

    /**
     * Run stty command and parse output to width & height.
     *
     * @return int[]
     */
    private static function getSttyColumns()
    {
        $info = static::runCommand('stty -a | grep columns');

        if ($info && preg_match('/rows.(\d+);.columns.(\d+);/i', $info, $matches)) {
            // extract [w, h] from "rows h; columns w;"
        } elseif ($info && preg_match('/;.(\d+).rows;.(\d+).columns/i', $info, $matches)) {
            // extract [w, h] from "; h rows; w columns"
        } else {
            return [];
        }

        return [(int) $matches[2], (int) $matches[1]];
    }

    /**
     * Run a command.
     *
     * @param string $command
     *
     * @return string|null
     */
    private static function runCommand($command)
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorSpec, $pipes, null, null, ['suppress_errors' => true]);
        if (!is_resource($process)) {
            return null;
        }

        $info = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $info;
    }
}
