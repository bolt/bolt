<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\ResourceManager;
use Bolt\Controller\ExceptionControllerInterface;

/**
 * Cache validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Cache implements ValidationInterface, ResourceManagerAwareInterface
{
    /** @var ResourceManager */
    private $resourceManager;

    /**
     * {@inheritdoc}
     */
    public function check(ExceptionControllerInterface $exceptionController)
    {
        $path = $this->resourceManager->getPath('cache');
        if (!is_dir($path)) {
            return $exceptionController->systemCheck(Validator::CHECK_CACHE, [], ['path' => $path]);
        }
        if (!is_writable($path)) {
            return $exceptionController->systemCheck(Validator::CHECK_CACHE, [], ['path' => $path]);
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
