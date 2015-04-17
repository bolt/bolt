<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Database pre-fill command
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabasePrefill extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('database:prefill')
            ->setDescription('Pre-fill the database Lorem Ipsum records')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addArgument('contenttypes', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'A list of Contentypes to pre-fill. If this argument is empty, all Contenttypes are used.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        $contenttypes = $input->getArgument('contenttypes');

        $this->app['storage']->preFill((array) $contenttypes);

        $this->auditLog(__CLASS__, 'Database pre-filled');
        $output->writeln('<info>Database pre-filled</info>');
    }
}
