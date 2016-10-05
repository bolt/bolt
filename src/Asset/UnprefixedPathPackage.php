<?php

namespace Bolt\Asset;

use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Removes a prefix from path before applying base path and version.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class UnprefixedPathPackage extends PathPackage
{
    /** @var string */
    private $prefix;

    /**
     * Constructor.
     *
     * @param string                   $prefix          A prefix to remove from path (before base path is applied).
     * @param string                   $basePath        The base path to be prepended to relative paths
     * @param VersionStrategyInterface $versionStrategy The version strategy
     * @param ContextInterface         $context         The request context
     */
    public function __construct($prefix, $basePath, VersionStrategyInterface $versionStrategy, ContextInterface $context)
    {
        parent::__construct($basePath, $versionStrategy, $context);
        $this->prefix = ltrim($prefix, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($path)
    {
        $path = $this->removePrefix($path);

        return parent::getVersion($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($path)
    {
        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        $path = $this->removePrefix($path);

        return parent::getUrl($path);
    }

    protected function removePrefix($path)
    {
        $path = ltrim($path, '/');

        if (strpos($path, $this->prefix) === 0) {
            $path = substr($path, strlen($this->prefix));
        }

        return $path;
    }
}
