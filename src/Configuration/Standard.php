<?php
namespace Bolt\Configuration;

use Bolt\Application;

/**
 * Left as a blank extension of ResourceManager for now, this semantically represents a default configuration
 * for a Bolt application.
 */
class Standard extends ResourceManager
{

    public function __construct($loader)
    {
        if ($loader instanceof \Pimple) {
            $container = $loader;
        } else {
            $container = new \Pimple();
            $container['rootpath'] = $loader;
        }

        parent::__construct($container);
    }
}
