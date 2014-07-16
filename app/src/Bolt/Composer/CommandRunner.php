<?php
namespace Bolt\Composer;

use Silex;
use Symfony\Component\HttpFoundation\JsonResponse;


class CommandRunner
{
    
    public $wrapper;
    public $messages;
    public $lastOutput;
    public $packageFile;
    
    public function __construct(Silex\Application $app, $packageRepo = null)
    {
        $this->packageRepo = $packageRepo;
        $this->packageFile = $app['resources']->getPath('root').'/composer.json';
        if(!is_writable($this->packageFile)) {
            $this->messages[] = sprintf(
                "The file '%s' is not writable. You will not be able to use this feature without changing the permissions.",
                $this->packageFile
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
    
    public function uninstall($package)
    {
        $json = json_decode(file_get_contents($this->packageFile));
        unset($json->require->$package);
        file_put_contents($this->packageFile, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        $response = $this->execute("update");
        if($response) {
            return "$package successfully removed";
        } else {
            return "$package could not be uninstalled. Try checking that your composer.json file is writable.";
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
                    $installed[]=$remote;
                }
            }
            
        }
        if(!count($installed)) {
            return "No Bolt extensions installed";
        } else {
            return new JsonResponse(json_encode($installed));
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
