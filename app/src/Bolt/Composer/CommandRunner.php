<?php
namespace Bolt\Composer;

use Silex;
use Symfony\Component\HttpFoundation\JsonResponse;


class CommandRunner
{
    
    public $wrapper;
    public $messages = array();
    public $lastOutput;
    public $packageFile;
    public $basedir;
    
    public function __construct(Silex\Application $app, $packageRepo = null)
    {
        $this->basedir = $app['resources']->getPath('extensions');
        $this->packageRepo = $packageRepo;
        $this->packageFile = $app['resources']->getPath('root').'/extensions/composer.json';        
        putenv("COMPOSER_HOME=".sys_get_temp_dir());
        $this->wrapper = \evidev\composer\Wrapper::create();
        
        if(!is_file($this->packageFile)) {
            $this->execute("init");
        }
        if(is_file($this->packageFile) && !is_writable($this->packageFile)) {
            $this->messages[] = sprintf(
                "The file '%s' is not writable. You will not be able to use this feature without changing the permissions.",
                $this->packageFile
            );
        }
        
        $this->execute("config repositories.bolt composer ".$app['extend.site']."satis/");
        $json = json_decode(file_get_contents($this->packageFile));
        $json->repositories->packagist = false;
        $basePackage = "bolt/bolt";
        $json->provide = new \stdClass;
        $json->provide->$basePackage = "1.7.*";
        file_put_contents($this->packageFile, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

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
        $updates = array();
        $packages = array_filter($this->execute("show -i -N"));
        foreach($packages as $package) {
            $response = $this->execute('update --dry-run '.$package);
            if(count($response)) {
                if ($response[0]=="Nothing to install or update") {
                    return $updates;
                }
                $updates[] = $package; 
            }
        }
        return $updates;
        
    }
    
    public function update($package)
    {
        $response = $this->execute("update $package");
        return implode(array_slice($response, 2), "<br>" );
    }
    
    public function install($package, $version)
    {
        $response = $this->execute("require $package $version");
        if(false !== $response) {
            $response = implode("<br>", $response);
            return $response;
        } else {
            $message = "The requested extension version could not be installed. The most likely reason is that the version"."\n";
            $message.= "requested is not compatible with this version of Bolt."."\n\n"; 
            $message.= "Check on the extensions site for more information.";
            return $message;
        }
    }
    
    public function installAll()
    {
        $response = $this->execute("install");
        return implode($response, "<br>" );
    }
    
    public function uninstall($package)
    {
        $json = json_decode(file_get_contents($this->packageFile));
        unset($json->require->$package);
        file_put_contents($this->packageFile, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        $response = $this->execute("update --prefer-dist");
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
            return new JsonResponse([]);
        } else {
            return new JsonResponse($installed);
        }
        
    }
    
    
    protected function execute($command)
    {
        set_time_limit(0);
        $command .= " -d ".$this->basedir;
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $responseCode = $this->wrapper->run($command, $output);
        if($responseCode == 0) {
            $outputText = $output->fetch();
            $outputText = $this->clean($outputText);
            return explode("\n",$outputText);
        } else {
            $this->lastOutput = $output->fetch();
            return false;
        }
    }
    
    /**
     * Output Cleaner:
     * Takes the console output and filters out messages that we don't want to pass on to the user
     *
     * @return void
     **/
    public function clean($output)
    {
        $clean = array(
            "Generating autoload files\n",
            "Loading composer repositories with package information\n",
            "Updating dependencies (including require-dev)\n",
            "Installing dependencies (including require-dev)\n",
            "Installing dependencies (including require-dev) from lock file\n"
        );
        return str_replace($clean, array(), $output);
        
    }
    
}
