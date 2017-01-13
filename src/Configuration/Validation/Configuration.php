<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Controller\ExceptionControllerInterface;
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
     * Constructor.
     *
     * @param string $baseName
     */
    public function __construct($baseName)
    {
        $this->baseName = $baseName;
    }

    /**
     * {@inheritdoc}
     */
    public function check(ExceptionControllerInterface $exceptionController)
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
    public function isTerminal()
    {
        return true;
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
