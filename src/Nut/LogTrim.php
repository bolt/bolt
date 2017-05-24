<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Nut command to clear the system & change logs.
 */
class LogTrim extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('log:trim')
            ->setDescription('Trim the system & change logs.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Trimming logs');
        $ask = !$input->getOption('no-interaction');
        $question = new ConfirmationQuestion('<question>Are you sure you want to trim the system & change logs?</question>', false);

        if ($ask && !$this->io->askQuestion($question)) {
            return 0;
        }

        $this->app['logger.manager']->trim('system');
        $this->app['logger.manager']->trim('change');

        $this->auditLog(__CLASS__, 'System system & change logs trimmed');
        $this->io->success('System & change logs trimmed!');

        return 0;
    }
}
