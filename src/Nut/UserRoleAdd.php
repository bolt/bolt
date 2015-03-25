<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to add a role to a Bolt user account
 */
class UserRoleAdd extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('role:add')
            ->setDescription('Add a certain role to a user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username (loginname) you wish to add a role to.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to give them.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        if ($this->app['users']->hasRole($username, $role)) {
            $msg = sprintf("\nUser '%s' already has role '%s'. No action taken.", $username, $role);
            $output->writeln($msg);
        } else {
            if ($this->app['users']->addRole($username, $role)) {
                $this->auditLog(__CLASS__, "Role $role granted to user $username");
                $msg = sprintf("\n<info>User '%s' now has role '%s'.</info>", $username, $role);
                $output->writeln($msg);
            } else {
                $msg = sprintf("\n<error>Could not add role '%s' to user '%s'.</error>", $role, $username);
                $output->writeln($msg);
            }
        }
    }
}
