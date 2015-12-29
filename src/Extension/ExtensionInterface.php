<?php

namespace Bolt\Extension;

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
     * The path should always be returned as a Unix path (with /).
     *
     * @return string
     */
    public function getPath();
}
