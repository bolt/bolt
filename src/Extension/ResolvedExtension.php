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
    /** @var PackageDescriptor|null */
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
     * Returns a unique identifier for the extension, such as: Vendor/Name.
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
     * Returns the extension vendor.
     *
     * @return string
     */
    public function getVendor()
    {
        return $this->innerExtension->getVendor();
    }

    /**
     * Returns the extension human friendly name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->innerExtension->getDisplayName();
    }

    /**
     * Returns the extension class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return get_class($this->innerExtension);
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
     * Return the extension's package descriptor.
     *
     * @return PackageDescriptor|null
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
    public function setDescriptor(PackageDescriptor $descriptor = null)
    {
        $this->descriptor = $descriptor;

        return $this;
    }

    /**
     * Returns whether the extension is managed by Bolt. This is the opposite of "bundled".
     *
     * @return bool
     */
    public function isManaged()
    {
        return (bool) $this->descriptor;
    }

    /**
     * Returns whether the extension is bundled by user. This is the opposite of "managed".
     *
     * @return bool
     */
    public function isBundled()
    {
        return !$this->isManaged();
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
        return $this->descriptor ? $this->descriptor->isValid() : true;
    }
}
