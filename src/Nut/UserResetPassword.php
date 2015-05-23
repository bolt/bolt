<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
                'The username (loginname or e-mail address) you wish to reset the password for.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');

        $password = $this->app['users']->setRandomPassword($username);

        if ($password !== false) {
            $output->writeln("<info>New password for {$username} is {$password}</info>");
        } else {
            $output->writeln("<error>Error no such user {$username}</error>");
        }
    }
}
