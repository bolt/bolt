<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Logger\FlashLoggerInterface;

/**
 * Configuration parameters validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Configuration implements ValidationInterface, ConfigAwareInterface, FlashLoggerAwareInterface
{
    /** @var Config */
    private $config;
    /** @var FlashLoggerInterface */
    private $flashLogger;

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $exceptions = $this->config->getExceptions();
        if ($exceptions === null) {
            return null;
        }

        foreach ($exceptions as $exception) {
            $this->flashLogger->error($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function setFlashLogger(FlashLoggerInterface $flashLogger)
    {
        $this->flashLogger = $flashLogger;
    }
}
