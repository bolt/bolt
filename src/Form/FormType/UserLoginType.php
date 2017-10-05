<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bolt user login form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserLoginType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['min' => 2]),
                    ],
                    'label' => Trans::__('page.login.label.username'),
                    'attr'  => [
                        'placeholder' => Trans::__('page.login.placeholder.username'),
                    ],
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                    'label' => Trans::__('page.login.label.password'),
                    'attr'  => [
                        'placeholder'  => Trans::__('page.login.placeholder.password'),
                        'autocomplete' => 'enter-password',
                    ],
                ]
            )
            ->add('forgot', ButtonType::class, ['label' => Trans::__('page.login.button.forgot-password')])
            ->add('back', ButtonType::class, ['label' => Trans::__('page.login.button.back')])
            ->add('reset', SubmitType::class, ['label' => Trans::__('page.login.button.reset-password')])
            ->add('login', SubmitType::class, ['label' => Trans::__('page.login.button.log-on')])
        ;

        $transformer = function ($username) { return mb_strtolower($username); };
        $builder->get('username')->addModelTransformer(new CallbackTransformer($transformer, $transformer));
    }
}
