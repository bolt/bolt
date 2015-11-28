<?php

namespace Bolt\Extension;

/**
 * This wraps an extension and provides additional functionality
 * that does not belong in the extension itself.
 *
 * This works similar to Symfony's ResolvedFormType.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ResolvedExtension
{
    /** @var ExtensionInterface */
    protected $innerExtension;

    /**
     * Constructor.
     *
     * @param ExtensionInterface $innerExtension
     */
    public function __construct(ExtensionInterface $innerExtension)
    {
        $this->innerExtension = $innerExtension;
    }

    /**
     * Returns the wrapped extension.
     *
     * @return \Bolt\Extension\ExtensionInterface
     */
    public function getInnerExtension()
    {
        return $this->innerExtension;
    }

    /**
     * Returns the extension name (the class short name).
     *
     * @return string
     */
    public function getName()
    {
        return $this->innerExtension->getName();
    }

    /**
     * Returns the extension namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->innerExtension->getNamespace();
    }

    /**
     * Returns the extensions directory path.
     *
     * The path should always be returned as a Unix path (with /).
     *
     * @return string
     */
    public function getPath()
    {
        return $this->innerExtension->getPath();
    }

    /**
     * Return the extension's base URL.
     *
     * @return string
     */
    public function getUrl()
    {
    }

    /**
     * Return the extension's install type, either 'composer' or 'local'.
     *
     * @return string
     */
    public function getInstallType()
    {
    }

    /**
     * Check if the extension is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
    }

    /**
     * Enable or disable an extension at runtime.
     *
     * @param bool|int $enabled
     */
    public function setEnabled($enabled)
    {
    }
}
