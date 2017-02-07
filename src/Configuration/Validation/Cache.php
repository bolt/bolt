<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\ResourceManager;
use Bolt\Exception\Configuration\Validation\System\CacheValidationException;

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
    public function check()
    {
        $path = $this->resourceManager->getPath('cache');
        if (!is_dir($path)) {
            throw new CacheValidationException($path);
        }
        if (!is_writable($path)) {
            throw new CacheValidationException($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setResourceManager(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }
}
