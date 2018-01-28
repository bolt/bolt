<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Bolt new user editing form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserNewType extends AbstractUserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this
            ->addUserName($builder)
            ->addPassword($builder, ['required' => true])
            ->addEmail($builder)
            ->addDisplayName($builder)
            ->addSave($builder, ['label' => Trans::__('general.phrase.create-user-first')])
        ;
    }
}
