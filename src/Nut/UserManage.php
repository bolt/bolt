<?php

namespace Bolt\Nut;

use Bolt\Common\Json;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository\UsersRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to manage a user.
 */
class UserManage extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:manage')
            ->setDescription('Manage a user.')
            ->addArgument('login', InputArgument::REQUIRED, 'The user name or email to manage.')
            ->addOption('enable', 'e', InputOption::VALUE_NONE, 'Enable the user.')
            ->addOption('disable', 'd', InputOption::VALUE_NONE, 'Disable the user.')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List the user details.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Repository\UsersRepository $repo */
        $repo = $this->app['storage']->getRepository(Entity\Users::class);

        $userLogin = $input->getArgument('login');
        $userEntity = $this->getUser($repo, $userLogin);
        if ($userEntity === false) {
            $this->io->error(sprintf('User account not found for %s', $userLogin));

            return 1;
        }

        if ($input->getOption('enable') && $input->getOption('disable')) {
            throw new \RuntimeException('You can not enable and disable a user at the same time.');
        }

        if ($input->getOption('enable')) {
            $this->io->title("Updating user: '$userLogin'");
            $userEntity->setEnabled(true);
            $repo->save($userEntity);
            $this->io->success("Enabled user: {$userEntity->getUsername()}");

            return 0;
        }

        if ($input->getOption('disable')) {
            $this->io->title("Updating user: '$userLogin'");
            $userEntity->setEnabled(false);
            $repo->save($userEntity);
            $this->io->success("Disabled user: {$userEntity->getUsername()}");

            return 0;
        }

        if ($input->getOption('list')) {
            $this->io->title("Account details for '$userLogin'");
            $headers = ['User name', 'Email', 'Display Name', 'Roles', 'Enabled'];
            $roles = array_filter($userEntity->getRoles(), function ($var) {
                return $var !== 'everyone';
            });
            $rows = [
                [
                    $userEntity->getUsername(),
                    $userEntity->getEmail(),
                    $userEntity->getDisplayname(),
                    implode(', ', $roles),
                    Json::dump($userEntity->getEnabled()),
                ],
            ];
            $this->io->table($headers, $rows);
        }

        return 0;
    }

    /**
     * @param UsersRepository $repo
     * @param string          $userLogin
     *
     * @return Entity\Users|false
     */
    protected function getUser(UsersRepository $repo, $userLogin)
    {
        $userEntity = $repo->findOneBy(['username' => $userLogin]);
        if ($userEntity) {
            return $userEntity;
        }

        return $repo->findOneBy(['email' => $userLogin]);
    }
}
