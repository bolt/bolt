<?php
namespace Bolt\Configuration;

/**
 * Inherits from default and adds some specific checks for composer installs.
 *
 * @author Ross Riley <riley.ross@gmail.com> 
 **/

class ComposerChecks extends LowlevelChecks
{
    
    public $composerSuffix = <<< EOM
    </strong></p><p>When using Bolt as a Composer package ensure you have taken the following steps:</p>
    <ol>
        <li>Create a local, writable config directory: <code>mkdir -p app/config && chmod -R 0777 app/config</code></li>
        <li>Create a local, writable cache directory: <code>mkdir -p app/cache && chmod -R 0777 app/cache</code></li>
        <li>For a default SQLite install, create a local, writable directory: <code>mkdir -p app/database && chmod -R 0777 app/database</code></li>
        <li>Create a local, writable extensions directory: <code>mkdir -p extensions && chmod -R 0777 extensions</code></li>
    </ol><strong>
EOM;


    /**
     * The constructor requires a resource manager object to perform checks against.
     * This should ideally be typehinted to Bolt\Configuration\ResourceManager
     *
     * @return void
     **/
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->addCheck('extensions', true);
        $this->addCheck('database', true);
        $this->addCheck('config', true);
    }
    
    
    
    public function checkConfig()
    {                    
        $this->checkDir($this->config->getPath('config'));
    }
    
    public function checkCache()
    {                    
        $this->checkDir($this->config->getPath('cache'));
    }
    
    public function checkDatabase()
    {                    
        $this->checkDir($this->config->getPath('database'));
    }
    
    public function checkExtensions()
    {                    
        $this->checkDir($this->config->getPath('extensions'));
    }
    
    
    protected function checkDir($location)
    {
        if (!is_dir($location)) {
            throw new LowlevelException(
                "The default folder <code>" . $location . 
                "</code> doesn't exist. Make sure it's " .
                "present and writable to the user that the webserver is using.". $this->composerSuffix);
        } elseif (!is_writable($location)) {
            throw new LowlevelException(
                "The default folder <code>" . $location . 
                "</code> isn't writable. Make sure it's writable to the user that the webserver is using.".$this->composerSuffix
            );
        }
    }


}
