<?php

namespace Bolt\Nut;

use Bolt\Collection\Bag;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * User command base class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractUser extends BaseCommand
{
    /**
     * @param InputInterface $input
     */
    protected function askUserName(InputInterface $input)
    {
        $userName = $input->getArgument('username');
        $question = new Question('User name', $userName);
        $userName = $this->io->askQuestion($question);
        $input->setArgument('username', $userName);
    }

    /**
     * @param InputInterface $input
     */
    protected function askDisplayName(InputInterface $input)
    {
        $displayName = $input->getArgument('displayname');
        $question = new Question('Display name', $displayName);
        $displayName = $this->io->askQuestion($question);
        $input->setArgument('displayname', $displayName);
    }

    /**
     * @param InputInterface $input
     */
    protected function askEmail(InputInterface $input)
    {
        $emailAddress = $input->getArgument('email');
        $question = new Question('Email address', $emailAddress);
        $emailAddress = $this->io->askQuestion($question);
        $input->setArgument('email', $emailAddress);
    }

    /**
     * @param InputInterface $input
     */
    protected function askPassword(InputInterface $input)
    {
        $question = new Question('Password');
        $question->setHidden(true);
        $password = $this->io->askQuestion($question);
        $input->setArgument('password', $password);
    }

    /**
     * @param InputInterface $input
     */
    protected function askRole(InputInterface $input)
    {
        $roles = (array) $input->getArgument('role');
        $choices = [];

        $allRoles = Bag::from($this->app['config']->get('permissions/roles'));
        $allRoles->setPath('root/label', 'Root');
        foreach ($allRoles->keys() as $role) {
            $choices[$role] = $allRoles->getPath("$role/label", $role);
        }
        $defaults = empty($roles) ? null : implode(', ', $roles);

        $question = new ChoiceQuestion('Roles (comma separated)', $choices, $defaults);
        $question->setMultiselect(true);
        $roles = $this->io->askQuestion($question);
        $input->setArgument('role', $roles);
    }
}
