<?php

namespace Bolt\Storage\Mapping;

use Bolt\Storage\Mapping\MappingManager;

/**
 * Interface for definitions that require an instance of the mapping loader.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
interface MappingAwareInterface
{
    /**
     * @param MappingManager $manager
     */
    public function setMappingManager(MappingManager $manager);
}
