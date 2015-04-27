<?php
namespace Bolt\Routing;

interface DefaultControllerClassAwareInterface
{
    /**
     * Sets the default controller class.
     *
     * This is this first part of a callable so it can be a string or an object.
     *
     * @param string|object $class
     */
    public function setDefaultControllerClass($class);
}
