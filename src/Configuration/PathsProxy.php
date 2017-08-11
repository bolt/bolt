<?php

namespace Bolt\Configuration;

use Bolt\Common\Deprecated;

/**
 * Bolt\Configuration\ResourceManager::getPaths() is proxied here, which used to return a simple array.
 *
 * This allows us to still use getPath, getUrl, and getRequest methods which have custom logic in them to maintain BC.
 *
 * @deprecated since 3.0, to be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class PathsProxy implements \ArrayAccess
{
    /** @var ResourceManager */
    protected $resources;

    /**
     * Constructor.
     *
     * @param ResourceManager $resources
     */
    public function __construct(ResourceManager $resources)
    {
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        Deprecated::warn(
            "\$app['paths']['$offset'] in PHP and {{ paths.$offset }} in Twig",
            3.0,
            'Instead use UrlGenerator or Asset Packages for urls, and Bolt\Filesystem (recommended)' .
            ' or PathResolver::resolve for filesystem paths.'
        );

        try {
            return $this->resources->getRequest($offset);
        } catch (\InvalidArgumentException $e) {
        }

        try {
            return $this->resources->getUrl($offset);
        } catch (\InvalidArgumentException $e) {
        }

        try {
            return $this->resources->getPath($offset);
        } catch (\InvalidArgumentException $e) {
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('You cannot change urls or paths.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('You cannot remove urls or paths.');
    }
}
