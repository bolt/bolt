<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to add a user to the system
 */
class UserAdd extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('user:add')
            ->setDescription('Add a new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username (loginname) you wish to add a role to.')
            ->addArgument('displayname', InputArgument::REQUIRED, 'The display name for the new user.')
            ->addArgument('email', InputArgument::REQUIRED, 'The email address for the new user.')
            ->addArgument('password', InputArgument::REQUIRED, 'The password for the new user.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to give them.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $email = $input->getArgument('email');
        $displayname = $input->getArgument('displayname');
        $role = $input->getArgument('role');

        $this->app['users']->getUsers();
        $user = $this->app['users']->getEmptyUser();
        $user['roles'] = array($role);
        $user['username'] = $username;
        $user['password'] = $password;
        $user['displayname'] = $displayname;
        $user['email'] = $email;

        $valid = true;
        if (! $this->app['users']->checkAvailability('username', $user['username'])) {
            $valid = false;
            $output->writeln("<error>Error creating user: username {$user['username']} already exists</error>");
        }
        if (! $this->app['users']->checkAvailability('email', $user['email'])) {
            $valid = false;
            $output->writeln("<error>Error creating user: email {$user['email']} exists</error>");
        }
        if (! $this->app['users']->checkAvailability('displayname', $user['displayname'])) {
            $valid = false;
            $output->writeln("<error>Error creating user: display name {$user['displayname']} already exists</error>");
        }

        if ($valid) {
            $res = $this->app['users']->saveUser($user);
            if ($res) {
                $this->auditLog(__CLASS__, "User created: {$user['username']}");
                $output->writeln("<info>Successfully created user: {$user['username']}</info>");
            } else {
                $output->writeln("<error>Error creating user: {$user['username']}</error>");
            }
        }
    }
}
