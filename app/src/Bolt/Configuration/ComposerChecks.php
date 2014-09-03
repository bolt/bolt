<?php
namespace Bolt\Configuration;

/**
 * Inherits from default and adds some specific checks for composer installs.
 *
 * @author Ross Riley <riley.ross@gmail.com> 
 **/

class ComposerChecks extends LowlevelChecks
{


    /**
     * The constructor requires a resource manager object to perform checks against.
     * This should ideally be typehinted to Bolt\Configuration\ResourceManager
     *
     * @return void
     **/
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->addCheck('app', true);
        $this->addCheck('config', true);
    }
    
    
    public function checkConfig()
    {
        $message =  "Bolt needs a local config directory to store site-specific configuration. ";             
                    
        if (!is_dir($this->config->getPath('config'))) {
            throw new LowlevelException(
                $message . "The default folder <code>" . $this->config->getPath('config') . 
                "</code> doesn't exist. Make sure it's " .
                "present and writable to the user that the webserver is using.");
        } elseif (!is_writable($this->config->getPath('config'))) {
            throw new LowlevelException(
                $message . "The default folder <code>" . $this->config->getPath('config') . 
                "</code> isn't writable. Make sure it's writable to the user that the webserver is using."
            );
        }
    }
    
    


}
