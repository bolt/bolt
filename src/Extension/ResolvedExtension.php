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

    public function getInnerExtension()
    {
        return $this->innerExtension;
    }

    public function getName()
    {
        return $this->innerExtension->getName();
    }

    public function getNamespace()
    {
        return $this->innerExtension->getNamespace();
    }

    public function getPath()
    {
        return $this->innerExtension->getPath();
    }

    public function getUrl()
    {
    }

    public function getInstallType()
    {
    }

    public function isEnabled()
    {
    }

    public function setEnabled($enabled)
    {
    }
}
