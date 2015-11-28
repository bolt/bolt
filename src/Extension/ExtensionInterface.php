<?php

namespace Bolt\Extension;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * ExtensionInterface.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface ExtensionInterface extends ServiceProviderInterface
{
    /**
     * Sets the application.
     *
     * @param Application $app
     */
    public function setApp(Application $app);

    /**
     * Returns the extension name (the class short name).
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the extension namespace.
     *
     * @return string
     */
    public function getNamespace();

    /**
     * Returns the extensions directory path.
     *
     * The path should always be returned as a Unix path (with /).
     *
     * @return string
     */
    public function getPath();
}
