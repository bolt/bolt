<?php

namespace Bolt\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;
use Bolt\Translation\Translator as Trans;
use Bolt\Storage\Entity\Invitations;
use Bolt\Storage\Entity;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form;

class InvitationType extends AbstractType
{
    /**
     * Create a form to send the invitation code by email with the form builder.
     *
     * @param object $form Generated form
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    public function getInvitationEmailForm($form)
    {
        $form
            ->add(
                'invitationLink',
                TextType::class,
                [
                    'label' => Trans::__('page.invitation.share-options.copy'),
                    'disabled' => true,
                ]
            )->add(
                'copy',
                ButtonType::class,
                [
                    'label' => Trans::__('page.invitation.button.copy'),
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ]
            )->add(
                'to',
                TextType::class,
                [
                    'constraints' => new Assert\Email(),
                    'label' => Trans::__('page.invitation.share-options.to-email'),
                    'attr' => [
                        'placeholder' => Trans::__('page.invitation.share-options.to-placeholder'),
                        'class' => 'to',
                    ],
                ]
            )
            ->add(
                'subject',
                TextType::class,
                [
                    'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 32])],
                    'label' => Trans::__('page.invitation.share-options.subject-email'),
                    'attr' => [
                        'placeholder' => Trans::__('page.invitation.share-options.subject-placeholder'),
                        'class' => 'subject',
                    ],
                ]
            )
            ->add(
                'message',
                TextareaType::class,
                [
                    'constraints' => [new Assert\NotBlank()],
                    'label' => Trans::__('page.invitation.share-options.message-email'),
                    'attr' => [
                        'placeholder' => Trans::__('page.invitation.share-options.message-placeholder'),
                        'class' => 'message',
                    ],
                ]
            );

        return $form;
    }

    /**
     * Create a form to generate an invitation code with the form builder.
     *
     * @param object $form
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    public function getGenerateInvitationForm($form, $definedRoles)
    {
        // Get the roles
        $roles = array_map(
            function ($role) {
                return $role['label'];
            },
            $definedRoles
        );

        $form
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'choices' => $roles,
                    'expanded' => true,
                    'multiple' => true,
                    'label' => Trans::__('page.invitation.label.assigned-roles'),
                ]
            )->add('expiration', DateTimeType::class, array(
                'input' => 'datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => trvaue,
                'abled' => false,
                'data' => new \DateTime("+1 week"),
                'label' => Trans::__('page.invitation.expiration-date'),

            ));

        return $form;
    }
}
