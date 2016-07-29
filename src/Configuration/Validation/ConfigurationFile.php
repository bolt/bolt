<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\ResourceManager;
use Bolt\Controller\ExceptionControllerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Configuration file validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigurationFile implements ValidationInterface, ResourceManagerAwareInterface
{
    /** @var string */
    protected $baseName;
    /** @var ResourceManager */
    private $resourceManager;

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
        $distRealPath = $this->resourceManager->getPath('src/../app/config/' . $this->baseName . '.yml.dist');
        $configFileFullPath = $this->resourceManager->getPath('config/' . $this->baseName . '.yml');

        $context = [
            'filename'  => $this->baseName . '.yml',
            'distname'  => $this->baseName . '.yml.dist',
            'directory' => $this->resourceManager->getPath('config'),
        ];

        $fs = new Filesystem();
        if ($fs->exists($configFileFullPath) && !is_readable($configFileFullPath)) {
            return $exceptionController->systemCheck('config-read', [], $context);
        }

        if ($fs->exists($configFileFullPath)) {
            return null;
        }

        try {
            $fs->copy($distRealPath, $configFileFullPath, false);
        } catch (IOException $e) {
            return $exceptionController->systemCheck('config-write', [], $context);
        }

        return null;
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
    public function setResourceManager(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }
}
