<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Pimple as Container;
use Silex\Application;
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
     * @return ServiceProviderInterface
     */
    public function getServiceProvider();

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
     * This should be used instead of getPath().
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
     * Returns the extension namespace.
     *
     * @return string
     */
    public function getNamespace();

    /**
     * Returns the extensions root directory path.
     *
     * This should only be used by core to configure root directory.
     *
     * The path should always be returned as a Unix path (with /).
     *
     * @return string
     */
    public function getPath();
}
