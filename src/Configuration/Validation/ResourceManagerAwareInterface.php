<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\ResourceManager;

/**
 * Interface for validation checks that require ResourceManager.
 *
 * @internal Do not use.
 *
 * @deprecated Deprecated since 3.1, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ResourceManagerAwareInterface
{
    /**
     * @param ResourceManager $resourceManager
     */
    public function setResourceManager(ResourceManager $resourceManager);
}
