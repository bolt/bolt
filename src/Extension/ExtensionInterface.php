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
     * @throws \LogicException If the extension has not been registered with a base directory.
     *
     * @return DirectoryInterface
     */
    public function getBaseDirectory();

    /**
     * Returns the web directory for the extension.
     *
     * The extension's assets should be installed in this folder.
     *
     * @throws \LogicException If the extension has not been registered with a web directory.
     *
     * @return DirectoryInterface
     */
    public function getWebDirectory();

    /**
     * Sets the web directory for the extension.
     *
     * @param DirectoryInterface $directory
     *
     * @return ExtensionInterface
     */
    public function setWebDirectory(DirectoryInterface $directory);

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

    /**
     * Returns the human friendly extension name.
     *
     * @return string
     */
    public function getDisplayName();
}
