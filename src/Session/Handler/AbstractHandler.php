<?php

namespace Bolt\Session\Handler;

/**
 * Implements common session handler methods that are not used.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class AbstractHandler implements \SessionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}
