<?php

namespace Bolt\Extension;

use Bolt\Composer\EventListener\PackageDescriptor;

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
    /** @var bool */
    protected $enabled;
    /** @var PackageDescriptor */
    protected $descriptor;

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
     * Returns a unique identifier for the extension, such as: Vendor/Name
     *
     * @return string
     */
    public function getId()
    {
        return $this->innerExtension->getId();
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
     * Returns the extension hman friendly name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->innerExtension->getDisplayName();
    }

    /**
     * Returns the root directory for the extension.
     *
     * @return \Bolt\Filesystem\Handler\DirectoryInterface
     */
    public function getBaseDirectory()
    {
        return $this->innerExtension->getBaseDirectory();
    }

    /**
     * Returns the extension's directory path relative to the extension root.
     *
     * @return string
     */
    public function getRelativePath()
    {
        return $this->descriptor->getPath();
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
     * Return the extension's package descriptor.
     *
     * @return PackageDescriptor
     */
    public function getDescriptor()
    {
        return $this->descriptor;
    }

    /**
     * Set the extension's package descriptor.
     *
     * @param PackageDescriptor $descriptor
     *
     * @return ResolvedExtension
     */
    public function setDescriptor($descriptor)
    {
        $this->descriptor = $descriptor;

        return $this;
    }

    /**
     * Return the extension's install type, either 'composer' or 'local'.
     *
     * @return string
     */
    public function getInstallType()
    {
        return $this->descriptor->getType();
    }

    /**
     * Check if the extension is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Enable or disable an extension at runtime.
     *
     * @param bool|int $enabled
     *
     * @return ResolvedExtension
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    /**
     * Check if the extension is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->descriptor->isValid();
    }
}
