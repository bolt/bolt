<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to add a role to a Bolt user account.
 */
class UserRoleAdd extends AbstractUser
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('role:add')
            ->setDescription('Add role(s) to a user account')
            ->addArgument('username', InputArgument::REQUIRED, 'The user (login) name you wish to add a role to')
            ->addArgument('role', InputArgument::REQUIRED ^ InputArgument::IS_ARRAY, 'The role(s) you wish to give them')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        try {
            $input->validate();
        } catch (RuntimeException $e) {
            $this->askUserName($input);
            $this->askRole($input);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $roles = $input->getArgument('role');
        $users = $this->app['users'];
        $permissions = $this->app['config']->get('permissions/roles', []);
        $result = 0;
        $ask = false;

        foreach ($roles as $key => $role) {
            if (!isset($permissions[$role])) {
                $this->io->warning("Invalid role '$role' given.");
                unset($roles[$key]);
                $ask = true;
            }
            if ($users->hasRole($username, $role)) {
                $this->io->note(sprintf("User '%s' already has role '%s'. No action taken.", $username, $role));
                unset($roles[$key]);
            }
        }

        if ($ask) {
            $input->setArgument('role', $roles);
            $this->askRole($input);
            $roles = $input->getArgument('role');
        }

        $messages = ['pass' => null, 'fail' => null];
        foreach ($roles as $key => $role) {
            if (!$users->addRole($username, $role)) {
                $messages['fail'][] = sprintf("Could not add role '%s' to user '%s'.", $role, $username);
                $result = 1;

                continue;
            }
            $this->auditLog(__CLASS__, "Role $role granted to user $username");
            $messages['pass'][] = sprintf("User '%s' now has role '%s'.", $username, $role);
        }

        if ($messages['fail']) {
            $this->io->error($messages['fail']);
        }
        if ($messages['pass']) {
            $this->io->success($messages['pass']);
        }

        return $result;
    }
}
