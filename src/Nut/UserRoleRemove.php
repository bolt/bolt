<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to remove a role from a Bolt user account.
 */
class UserRoleRemove extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('role:remove')
            ->setDescription('Remove a certain role from a user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username (loginname) you wish to remove the role from.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to remove.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');
        $users = $this->app['users'];

        if (!$users->hasRole($username, $role)) {
            $msg = sprintf("User '%s' doesn't already have role '%s'. No action taken.", $username, $role);
            $this->io->note($msg);

            return 1;
        }

        if ($users->removeRole($username, $role)) {
            $this->auditLog(__CLASS__, "Role $role removed from user $username");
            $msg = sprintf("User '%s' no longer has role '%s'.", $username, $role);
            $this->io->success($msg);

            return 0;
        }

        $msg = sprintf("Could not remove role '%s' from user '%s'.", $role, $username);
        $this->io->error($msg);

        return 1;
    }
}
