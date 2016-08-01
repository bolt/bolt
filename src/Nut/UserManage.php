<?php

namespace Bolt\Nut;

use Bolt\Storage\Entity;
use Bolt\Storage\Repository\UsersRepository;
use Symfony\Component\Console\Helper\Table;
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
        $repo = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users');
        
        $userLogin = $input->getArgument('login');
        $userEntity = $this->getUser($repo, $userLogin);
        if ($userEntity === false) {
            $output->writeln(sprintf('<error>User account not found for %s</error>', $userLogin));

            return;
        }

        if ($input->getOption('enable')) {
            $userEntity->setEnabled(true);
            $repo->save($userEntity);
            $output->writeln("<info>Enabled user: {$userEntity->getUsername()}</info>");
        }

        if ($input->getOption('disable')) {
            $userEntity->setEnabled(false);
            $repo->save($userEntity);
            $output->writeln("<info>Disabled user: {$userEntity->getUsername()}</info>");
        }

        if ($input->getOption('list')) {
            $table = new Table($output);
            $table
                ->setHeaders(['User name', 'Email', 'Display Name', 'Roles', 'Enabled'])
                ->addRow([
                    $userEntity->getUsername(),
                    $userEntity->getEmail(),
                    $userEntity->getDisplayname(),
                    implode(', ', $userEntity->getRoles()),
                    json_encode($userEntity->getEnabled()),
                ])
            ;
            $table->render();
        }
    }

    /**
     * @param UsersRepository $repo
     * @param string          $userLogin
     *
     * @return Entity\Users
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
