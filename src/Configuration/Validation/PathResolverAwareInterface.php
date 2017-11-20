<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\PathResolver;

/**
 * Interface for validation checks that require ResourceManager.
 *
 * @internal do not use
 *
 * @deprecated Deprecated since 3.1, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface PathResolverAwareInterface
{
    /**
     * @param PathResolver $pathResolver
     */
    public function setPathResolver(PathResolver $pathResolver);
}
