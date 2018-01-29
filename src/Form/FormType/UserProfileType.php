<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Bolt user profile editing form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserProfileType extends AbstractUserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this
            ->addPassword($builder, ['required' => false])
            ->addEmail($builder)
            ->addDisplayName($builder)
            ->addSave($builder, ['label' => Trans::__('page.edit-users.button.save')])
        ;
    }
}
