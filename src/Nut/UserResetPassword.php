<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Nut command to reset a user password.
 */
class UserResetPassword extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:reset-password')
            ->setDescription('Reset a user password.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username (login name or e-mail address) you wish to reset the password for.')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userName = $input->getArgument('username');

        $this->io->title("Resetting password for user '$userName'");
        $ask = !$input->getOption('no-interaction');
        $question = new ConfirmationQuestion("<question>Are you sure you want to reset the password for '$userName'?</question>", false);
        if ($ask && !$this->io->askQuestion($question)) {
            return 0;
        }

        // Boot all service providers manually as, we're not handling a request
        $this->app->boot();
        $password = $this->app['access_control.password']->setRandomPassword($userName);
        if ($password === false) {
            $this->io->error("Error no such user $userName");

            return 1;
        }
        $this->io->success("New password for $userName is $password");

        return 0;
    }
}
