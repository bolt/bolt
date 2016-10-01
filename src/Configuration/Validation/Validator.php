<?php

namespace Bolt\Configuration\Validation;

use Bolt\Config;
use Bolt\Configuration\LowlevelChecks;
use Bolt\Configuration\ResourceManager;
use Bolt\Controller;
use Bolt\Exception\BootException;
use Symfony\Component\HttpFoundation\Response;

/**
 * System validator.
 *
 * @internal For BC. Use of this class should not include use of LowlevelChecks
 *           methods/properties.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Validator extends LowlevelChecks implements ValidatorInterface
{
    const CHECK_APACHE = 'apache';
    const CHECK_CACHE = 'cache';
    const CHECK_CONFIG = 'configuration';
    const CHECK_DATABASE = 'database';
    const CHECK_MAGIC_QUOTES = 'magic-quotes';
    const CHECK_SAFE_MODE = 'safe-mode';

    /** @var Controller\Exception */
    private $exceptionController;
    /** @var Config */
    private $configManager;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var array */
    private $check = [
        self::CHECK_CONFIG       => Configuration::class,
        self::CHECK_DATABASE     => Database::class,
        self::CHECK_MAGIC_QUOTES => MagicQuotes::class,
        self::CHECK_SAFE_MODE    => SafeMode::class,
        self::CHECK_CACHE        => Cache::class,
        self::CHECK_APACHE       => Apache::class,
    ];

    /**
     * Constructor.
     *
     * @param Controller\Exception $exceptionController
     * @param Config               $config
     * @param ResourceManager      $resourceManager
     */
    public function __construct(Controller\Exception $exceptionController, Config $config, ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);
        $this->exceptionController = $exceptionController;
        $this->configManager = $config;
        $this->resourceManager = $resourceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function add($checkName, $className, $prepend = false)
    {
        if ($prepend) {
            $this->check = [$checkName => $className] + $this->check;
        } else {
            $this->check[$checkName] = $className;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($checkName)
    {
        return isset($this->check[$checkName]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($checkName)
    {
        unset($this->check[$checkName]);
    }

    /**
     * {@inheritdoc}
     */
    public function check($checkName)
    {
        if ($this->disableApacheChecks && $checkName === 'apache') {
            return null;
        }

        $className = $this->check[$checkName];

        return $this
            ->getValidator($className, $checkName)
            ->check($this->exceptionController)
        ;
    }

    /**
     * Run system installation checks.
     */
    public function checks()
    {
        if ($this->disableApacheChecks) {
            unset($this->check['apache']);
        }

        foreach ($this->check as $checkName => $className) {
            $response = $this
                ->getValidator($className, $checkName)
                ->check($this->exceptionController)
            ;
            if ($response instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Get a validator object from a given class name.
     *
     * @param string|ValidationInterface $className
     * @param mixed                      $constructorArgs
     *
     * @return ValidationInterface
     */
    private function getValidator($className, $constructorArgs)
    {
        /** @var ValidationInterface $validator */
        $validator = is_string($className) ? new $className($constructorArgs) : $className;
        if (!$validator instanceof ValidationInterface) {
            throw new BootException(sprintf('System validator was given a validation class %s that does not implement %s', $className, ValidationInterface::class));
        }
        if ($validator instanceof ResourceManagerAwareInterface) {
            $validator->setResourceManager($this->resourceManager);
        }
        if ($validator instanceof ConfigAwareInterface) {
            $validator->setConfig($this->configManager);
        }

        return $validator;
    }
}
