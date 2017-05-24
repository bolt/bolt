<?php

namespace Bolt\Nut;

use Bolt\Form\FormType;
use Bolt\Storage\Entity;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Nut command to add a user to the system.
 */
class UserAdd extends AbstractUser
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:add')
            ->setDescription('Add a new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'User (login) name for the new user')
            ->addArgument('displayname', InputArgument::REQUIRED, 'Display name for the new user')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address for the new user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the new user')
            ->addArgument('role', InputArgument::REQUIRED ^ InputArgument::IS_ARRAY, 'Role(s) you wish to give them')
            ->addOption('enable', 'e', InputOption::VALUE_NONE, 'Enable the new user upon creation')
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
            $this->askDisplayName($input);
            $this->askEmail($input);
            $this->askPassword($input);
            $this->askRole($input);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Repository\UsersRepository $repo */
        $repo = $this->app['storage']->getRepository(Entity\Users::class);
        $userEntity = new Entity\Users([
            'username'    => $input->getArgument('username'),
            'password'    => $input->getArgument('password'),
            'email'       => $input->getArgument('email'),
            'displayname' => $input->getArgument('displayname'),
            'roles'       => (array) $input->getArgument('role'),
            'enabled'     => (bool) $input->getOption('enable'),
        ]);

        $messages = $this->validate($userEntity);
        if ($messages !== null) {
            $messages[] = 'Error creating user:';
            $this->io->error(array_reverse($messages));

            return 1;
        }

        // Boot all service providers manually as, we're not handling a request
        $this->app->boot();
        $repo->save($userEntity);
        $this->auditLog(__CLASS__, "User created: {$userEntity->getUsername()}");

        $userCommand = $this->getApplication()->find('user:manage');
        $userCommand->run(new ArrayInput([
            'login'  => $input->getArgument('username'),
            '--list' => true,
        ]), $output);
        $this->io->success("Successfully created user: {$userEntity->getUsername()}");

        return 0;
    }

    /**
     * @param Entity\Users $userEntity
     *
     * @return array|null
     */
    private function validate(Entity\Users $userEntity)
    {
        $options = [
            'allow_extra_fields' => true,
            'csrf_protection'    => false,
        ];

        /** @var FormInterface $form */
        $form = $this->app['form.factory']->createBuilder(FormType\UserNewType::class, $userEntity, $options)
            ->getForm()
            ->submit($userEntity->toArray())
        ;
        $errors = $form->getErrors(true);
        if ($errors->count() === 0) {
            return null;
        }

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return $messages;
    }
}
