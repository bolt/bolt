<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Bolt user invitation redemption form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class InviteRedeemType extends AbstractUserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this
            ->addUserName($builder)
            ->addPassword($builder, ['required' => true])
            ->addEmail($builder, [], true)
            ->addDisplayName($builder)
            ->addSave($builder, ['label' => Trans::__('page.invitation.create-button')])
        ;
    }
}
