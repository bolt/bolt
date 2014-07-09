<?php
namespace Bolt\Composer;

use Silex;

class CommandRunner
{
    
    public $wrapper;
    
    public function __construct(Silex\Application $app)
    {
        
        $packagefile = $app['resources']->getPath('root').'/composer.json';
        if(!is_writable($packagefile)) {
            throw new \RuntimeException("$packagefile is not writable. Please try changing permissions.", 1);  
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
        $code = $composer->run($command, $output);
        if($code == 0) {
            $outputText = explode("\n",$output->fetch());
            return $outputText;
        } 
    }
    
}
