<?php

namespace Bolt\Logger;

/**
 * FlashBag logger interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface FlashLoggerInterface
{
    /**
     * Display a 'danger' message.
     *
     * @param string $message
     *
     * @return void
     */
    public function danger($message);

    /**
     * Display a 'error' message.
     *
     * @param string $message
     *
     * @return void
     */
    public function error($message);

    /**
     * Display a 'info' message.
     *
     * @param string $message
     *
     * @return void
     */
    public function info($message);

    /**
     * Display a 'success' message.
     *
     * @param string $message
     *
     * @return void
     */
    public function success($message);

    /**
     * Display a 'warning' message.
     *
     * @param string $message
     *
     * @return void
     */
    public function warning($message);

    /**
     * Has messages for a given type?
     *
     * @param string $type
     *
     * @return boolean
     */
    public function has($type);

    /**
     * Clear out messages.
     */
    public function clear();
}
