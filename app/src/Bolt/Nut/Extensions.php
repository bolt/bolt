<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Extensions extends BaseCommand
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
            print_r($ext); exit;
            $rows[]= array($ext->name, $ext->version, $ext->desc);
        }
        
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Name', 'Version', 'Description'))
            ->setRows($rows);
        $table->render($output);
        
    }
}
