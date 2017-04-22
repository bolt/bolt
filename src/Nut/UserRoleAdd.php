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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('role:add')
            ->setDescription('Add a certain role to a user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username (loginname) you wish to add a role to.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to give them.')
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
        $permissions = $this->app['config']->get('permissions/roles', []);
        if (!isset($permissions[$role])) {
            $this->io->error("Invalid role '$role' given. Failed to update user.");
            $this->io->text('Avaliable role options are:');
            $this->io->listing(array_keys($permissions));

            return 1;
        }

        if ($users->hasRole($username, $role)) {
            $msg = sprintf("User '%s' already has role '%s'. No action taken.", $username, $role);
            $this->io->note($msg);

            return 0;
        }
        if ($users->addRole($username, $role)) {
            $this->auditLog(__CLASS__, "Role $role granted to user $username");
            $msg = sprintf("User '%s' now has role '%s'.", $username, $role);
            $this->io->success($msg);

            return 0;
        }

        $msg = sprintf("Could not add role '%s' to user '%s'.", $role, $username);
        $this->io->error($msg);

        return 1;
    }
}
