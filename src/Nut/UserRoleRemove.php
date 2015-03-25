<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to remove a role from a Bolt user account
 */
class UserRoleRemove extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('role:remove')
            ->setDescription('Remove a certain role from a user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username (loginname) you wish to remove the role from.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to remove.');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        if (!$this->app['users']->hasRole($username, $role)) {
            $msg = sprintf("\nUser '%s' already doesn't have role '%s'. No action taken.", $username, $role);
            $output->writeln($msg);
        } else {
            if ($this->app['users']->removeRole($username, $role)) {
                $this->auditLog(__CLASS__, "Role $role removed from user $username");
                $msg = sprintf("\n<info>User '%s' no longer has role '%s'.</info>", $username, $role);
                $output->writeln($msg);
            } else {
                $msg = sprintf("\n<error>Could not remove role '%s' from user '%s'.</error>", $role, $username);
                $output->writeln($msg);
            }
        }
    }
}
