<?php

namespace Bolt\Twig;

/**
 * {@see SecurityPolicy} will check for this interface to get the class name
 * of the object to verify (instead of {@see get_class} by default).
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface SecurityProxyInterface
{
    /**
     * Gets the proxied class name.
     *
     * @return string
     */
    public function getProxiedClass();
}
