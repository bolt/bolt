<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\PathResolver;
use Bolt\Exception\Configuration\Validation\System\CacheValidationException;

/**
 * Cache validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Cache implements ValidationInterface, PathResolverAwareInterface
{
    /** @var PathResolver */
    private $pathResolver;

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $path = $this->pathResolver->resolve('cache');
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
    public function setPathResolver(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }
}
