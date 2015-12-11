<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Nut command to clear the system & change logs
 */
class LogClear extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('log:clear')
            ->setDescription('Clear (truncate) the system & change logs.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, no confirmation will be required')
        ;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $force = $input->getOption('force');
        $question = new ConfirmationQuestion('<question>Are you sure you want to clear the system & change logs?</question> ');

        if (!$force && !$helper->ask($input, $output, $question)) {
            return false;
        }

        $this->app['logger.manager']->clear('system');
        $this->app['logger.manager']->clear('change');

        $this->auditLog(__CLASS__, 'System system & change logs cleared');
        $output->writeln('<info>System & change logs cleared!</info>');
    }
}
