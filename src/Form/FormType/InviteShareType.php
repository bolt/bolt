<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Invitation sharing form.
 *
 * @author Carlos Perez <mrcarlosdev@gmail.com>
 */
class InviteShareType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'to',
                TextType::class,
                [
                    'constraints' => [
                        new Assert\Email([
                            'strict'  => false,
                            'checkMX' => true,
                        ]),
                    ],
                    'label'       => Trans::__('page.invitation.share.email-to'),
                    'attr'        => [
                        'placeholder' => Trans::__('page.invitation.share.email-to-placeholder'),
                    ],
                ]
            )
            ->add(
                'subject',
                TextType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['min' => 3, 'max' => 128]),
                    ],
                    'label' => Trans::__('page.invitation.share.email-subject'),
                    'attr'  => [
                        'placeholder' => Trans::__('page.invitation.share.email-subject-placeholder'),
                    ],
                ]
            )
            ->add(
                'message',
                TextareaType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'label' => Trans::__('page.invitation.share.email-message'),
                    'attr'  => [
                        'placeholder' => Trans::__('page.invitation.share.email-message-placeholder'),
                    ],
                ]
            )
            ->add('send', SubmitType::class, ['label' => Trans::__('page.invitation.share.email-send')])
        ;
    }
}
