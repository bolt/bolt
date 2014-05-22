<?php

namespace Bolt\Configuration;

use Bolt\Application;


/**
 * A Class to handle lookups and checks of all paths and resources within a Bolt App.
 *
 * Intended to simplify the ability to override resource location 
 *
 *
 * @author Ross Riley, riley.ross@gmail.com
 *
 */
class ComposerResources extends ResourceManager
{

    
    public function initialize()
    {
        parent::initialize();
        $this->setPath("apppath", $this->root."/vendor/bolt/bolt/app");
        $this->setPath("extensionspath", $this->root."/vendor/bolt/bolt/app/extensions");
        $this->setUrl("app", "/bolt-public/");
    }


    public function compat()
    {
        if(!defined("BOLT_COMPOSER_INSTALLED")) {
            define('BOLT_COMPOSER_INSTALLED', true);
        }
        parent::compat();
    }



}