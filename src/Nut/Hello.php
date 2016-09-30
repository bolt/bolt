<?php

namespace Bolt\Nut;

use Bolt\Translation\Translator as Trans;
use Bolt\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to output phpinfo()
 */
class Hello extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('hello')
            ->setDescription('Greet the user (and perform initial setup tasks).')
        ;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = sprintf(
            "<info>%s</info> - %s <comment>%s</comment>\n",
            Trans::__('nut.greeting'),
            Trans::__('nut.version'),
            Version::VERSION
        );

        $output->write($message);
    }
}
