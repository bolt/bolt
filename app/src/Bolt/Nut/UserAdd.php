<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserAdd extends BaseCommand
{
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $email = $input->getArgument('email');
        $displayname = $input->getArgument('displayname');
        $role = $input->getArgument('role');
        
        $user = $this->app['users']->getEmptyUser();
        $user['roles'] = array($role);
        $user['username'] = $username;
        $user['password'] = $password;
        $user['displayname'] = $displayname;
        $user['email'] = $email;
        
        $res = $this->app['users']->saveUser($user);
        print_r($res); exit;
        if ($res) {
            $output->writeln("<info>Successfully created user: {$res->username}</info>");
        } else {
            $output->writeln("<error>Error creating user: {$res->username}</error>");
        }
    }
}
