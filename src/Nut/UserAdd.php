<?php

namespace Bolt\Nut;

use Bolt\Storage\Entity;
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
            ->addArgument('role', InputArgument::REQUIRED, 'The role you wish to give them.')
        ;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Repository\UsersRepository $repo */
        $repo = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users');
        $user = new Entity\Users([
            'username'    => $input->getArgument('username'),
            'password'    => $input->getArgument('password'),
            'email'       => $input->getArgument('email'),
            'displayname' => $input->getArgument('displayname'),
            'roles'       => (array) $input->getArgument('role'),
        ]);

        $message = [];
        $valid = true;
        if ($repo->getUser($user->getEmail())) {
            $valid = false;
            $message[] = ("<error>    * Email address '{$user->getEmail()}' already exists</error>");
        }
        if ($repo->getUser($user->getUsername())) {
            $valid = false;
            $message[] = ("<error>    * User name '{$user->getUsername()}' already exists</error>");
        }
        if ($valid === false) {
            $message[] = ('<error>Error creating user:</error>');
            $output->write(array_reverse($message), true);

            return;
        }

        // Boot all service providers manually as, we're not handling a request
        $this->app->boot();
        $this->app['storage']->getRepository('Bolt\Storage\Entity\Users')->save($user);
        $this->auditLog(__CLASS__, "User created: {$user->getUsername()}");
        $output->writeln("<info>Successfully created user: {$user->getUsername()}</info>");
    }
}
