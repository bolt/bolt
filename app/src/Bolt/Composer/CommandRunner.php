<?php
namespace Bolt\Composer;

use Silex;

class CommandRunner
{
    
    public $wrapper;
    public $messages;
    public $lastOutput;
    
    public function __construct(Silex\Application $app, $packageRepo = null)
    {
        $this->packageRepo = $packageRepo;
        $packagefile = $app['resources']->getPath('root').'/composer.json';
        if(!is_writable($packagefile)) {
            $this->messages[] = sprintf(
                "The file '%s' is not writable. You will not be able to use this feature without changing the permissions.",
                $packagefile
            );
        }
        
        putenv("COMPOSER_HOME=".sys_get_temp_dir());
        $this->wrapper = \evidev\composer\Wrapper::create();
        try {
            $json = json_decode((file_get_contents($this->packageRepo)));
            $this->available = $json->packages;
        } catch (\Exception $e) {
            $this->messages[] = sprintf(
                "The Bolt extensions Repo at %s is currently unavailable. Please try again shortly.",
                $this->packageRepo
            );
        }
        
    }
    
    
    public function check()
    {
        $response = $this->execute("update --dry-run");
        if($response[2] === "") {
            return "All packages are up to date";
        } else {
            return array_slice($response, 2);
        }
        
    }
    
    public function install($package, $version)
    {
        $response = $this->execute("require $package $version");
        if(false !== $response) {
            $response = implode("<br>", array_slice($response, 2));
            return $response;
        } else {
            $message = "The requested extension version could not be installed. The most likely reason is that the version"."\n";
            $message.= "requested is not compatible with this version of Bolt."."\n\n"; 
            $message.= "Check on the extensions site for more information.";
            return $message;
        }
    }
    
    public function installed()
    {
        $installed = array();
        $all = $this->execute("show -i");
        $available = $this->available;
        
        foreach($this->available as $remote) {
                    
            foreach($all as $local) {
                if(strpos($local, $remote->name) !==false ) {
                    $installed[]=$local;
                }
            }
            
        }
        if(!count($installed)) {
            return "No Bolt extensions installed";
        } else {
            return implode("<br>", $installed);
        }
        
    }
    
    
    protected function execute($command)
    {
        set_time_limit(0);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $responseCode = $this->wrapper->run($command, $output);
        if($responseCode == 0) {
            $outputText = explode("\n",$output->fetch());
            return $outputText;
        } else {
            $this->lastOutput = $output->fetch();
            return false;
        }
    }
    
}
