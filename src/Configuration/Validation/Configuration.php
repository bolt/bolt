<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Controller\ExceptionControllerInterface;

/**
 * Configuration parameters validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Configuration implements ValidationInterface, ConfigAwareInterface
{
    /** @var Config */
    private $config;

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

        return $exceptionController->systemCheck(Validator::CHECK_CONFIG, $exceptions);
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
}
