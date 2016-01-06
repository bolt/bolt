<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Pimple as Container;
use Silex\ServiceProviderInterface;

/**
 * ExtensionInterface.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface ExtensionInterface
{
    /**
     * Returns the service provider.
     *
     * @return ServiceProviderInterface[]
     */
    public function getServiceProviders();

    /**
     * Sets the container.
     *
     * @param Container $container
     */
    public function setContainer(Container $container);

    /**
     * Sets the root directory for the extension
     * with filesystem configured in core.
     *
     * @param DirectoryInterface $directory
     *
     * @return ExtensionInterface
     */
    public function setBaseDirectory(DirectoryInterface $directory);

    /**
     * Returns the root directory for the extension.
     *
     * @return DirectoryInterface
     */
    public function getBaseDirectory();

    /**
     * Sets the extension's relative URL.
     *
     * @param string
     *
     * @return ExtensionInterface
     */
    public function setRelativeUrl($relativeUrl);

    /**
     * Returns the extension's relative URL.
     *
     * @return string
     */
    public function getRelativeUrl();

    /**
     * Returns a unique identifier for the extension, such as: Vendor/Name
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the extension name (the class short name).
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the extension vendor.
     *
     * @return string
     */
    public function getVendor();
}
