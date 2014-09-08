<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Extensions extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('extensions')
            ->setDescription('Lists all installed extensions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->app['extend.runner']->installed();
        $json = $result->getContent();
        
        foreach(json_decode($json) as $ext) {
            $rows[]= array($ext->name, $ext->version, $ext->type, $ext->descrip);
        }
        
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Name', 'Version', 'Type',  'Description'))
            ->setRows($rows);
        $table->render($output);
        
    }
}
