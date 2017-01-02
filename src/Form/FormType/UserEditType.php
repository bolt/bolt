<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Bolt user editing form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserEditType extends AbstractUserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this
            ->addUserName($builder)
            ->addPassword($builder, ['required' => false])
            ->addEmail($builder)
            ->addDisplayName($builder)
            ->addEnabled($builder)
            ->addRoles($builder)
            ->addLastSeen($builder)
            ->addLastIp($builder)
            ->addSave($builder, ['label' => Trans::__('page.edit-users.button.save')])
        ;
    }
}
