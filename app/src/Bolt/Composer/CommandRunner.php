<?php
namespace Bolt\Composer;

use Silex;

class CommandRunner
{
    
    public $wrapper;
    public $messages;
    
    public function __construct(Silex\Application $app)
    {
        
        $packagefile = $app['resources']->getPath('root').'/composer.json';
        if(!is_writable($packagefile)) {
            $this->messages[] = sprintf(
                "The file '%s' is not writable. You will not be able to use this feature without changing the permissions.",
                array('%s' => $packagefile)
            );
        }
        
        putenv("COMPOSER_HOME=".sys_get_temp_dir());
        $this->wrapper = \evidev\composer\Wrapper::create();
        
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
    
    
    protected function execute($command)
    {
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $responseCode = $this->wrapper->run($command, $output);
        if($responseCode == 0) {
            $outputText = explode("\n",$output->fetch());
            return $outputText;
        } 
    }
    
}
