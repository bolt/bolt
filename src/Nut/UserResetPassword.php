<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to reset a user password
 */
class UserResetPassword extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('user:reset-password')
            ->setDescription('Reset a user password.')
            ->addArgument('username', InputArgument::REQUIRED,
                'The username (loginname or e-mail address) you wish to reset the password for.')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE,
                'Do not ask for confirmation');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');

        /** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');
        $confirm = $input->getOption('no-interaction');
        $question = "<question>Are you sure you want to reset the password for \"{$username}\"?</question>";

        if (!$confirm && !$dialog->askConfirmation($output, $question, false)) {
            return false;
        }

        $password = $this->app['authentication.password']->setRandomPassword($username);

        if ($password !== false) {
            $output->writeln("<info>New password for {$username} is {$password}</info>");
        } else {
            $output->writeln("<error>Error no such user {$username}</error>");
        }
    }
}
