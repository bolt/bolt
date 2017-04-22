<?php

namespace Bolt\Nut;

use Bolt\Storage\Entity;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to add a user to the system
 */
class UserAdd extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:add')
            ->setDescription('Add a new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The user name (login name) for the new user.')
            ->addArgument('displayname', InputArgument::REQUIRED, 'The display name for the new user.')
            ->addArgument('email', InputArgument::REQUIRED, 'The email address for the new user.')
            ->addArgument('password', InputArgument::REQUIRED, 'The password for the new user.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to give them.')
            ->addOption('enable', 'e', InputOption::VALUE_NONE, 'Enable the new user.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Repository\UsersRepository $repo */
        $repo = $this->app['storage']->getRepository(Entity\Users::class);
        $user = new Entity\Users([
            'username'    => $input->getArgument('username'),
            'password'    => $input->getArgument('password'),
            'email'       => $input->getArgument('email'),
            'displayname' => $input->getArgument('displayname'),
            'roles'       => (array) $input->getArgument('role'),
            'enabled'     => (bool) $input->getOption('enable'),
        ]);

        $message = [];
        $valid = true;
        if ($repo->getUser($user->getEmail())) {
            $valid = false;
            $message[] = "    * Email address '{$user->getEmail()}' already exists";
        }
        if ($repo->getUser($user->getUsername())) {
            $valid = false;
            $message[] = "    * User name '{$user->getUsername()}' already exists";
        }
        if ($valid === false) {
            $message[] = 'Error creating user:';
            $this->io->error(array_reverse($message));

            return 1;
        }

        // Boot all service providers manually as, we're not handling a request
        $this->app->boot();
        $this->app['storage']->getRepository(Entity\Users::class)->save($user);
        $this->auditLog(__CLASS__, "User created: {$user->getUsername()}");

        $userCommand = $this->getApplication()->find('user:manage');
        $userCommand->run(new ArrayInput([
            'login'  => $input->getArgument('username'),
            '--list' => true,
        ]), $output);
        $this->io->success("Successfully created user: {$user->getUsername()}");

        return 0;
    }
}
