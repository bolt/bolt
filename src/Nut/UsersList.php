<?php

namespace Bolt\Nut;

use Bolt\Common\Json;
use Bolt\Storage\Entity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to list all users.
 */
class UsersList extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('users:list')
            ->setDescription('List all Bolt users')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Repository\UsersRepository $repo */
        $repo = $this->app['storage']->getRepository(Entity\Users::class);

        $userEntities = $repo->findAll();
        if ($userEntities === false) {
            $this->io->error('No user accounts found.');

            return 1;
        }

        $this->io->title("Account details for all users");
        $headers = ['User name', 'Email', 'Display Name', 'Roles', 'Enabled'];

        /** @var Entity\Users $userEntity */
        foreach ($userEntities as $userEntity) {
            $roles = array_filter($userEntity->getRoles(), function ($var) {
                return $var !== 'everyone';
            });
            $rows[] = [
                $userEntity->getUsername(),
                $userEntity->getEmail(),
                $userEntity->getDisplayname(),
                implode(', ', $roles),
                Json::dump($userEntity->getEnabled()),
            ];
        }

        $this->io->table($headers, $rows);

        return 0;
    }
}
