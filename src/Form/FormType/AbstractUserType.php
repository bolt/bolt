<?php

namespace Bolt\Form\FormType;

use Bolt\Collection\Bag;
use Bolt\Form\Validator\Constraints as UsersAssert;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bolt base user form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractUserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['roles', 'mutable']);
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'empty_data' => null,
            'mutable'    => [],
        ]);
        $resolver->setDefined([
            'id',
            'username',
            'displayname',
            'email',
            'password',
            'enabled',
            'lastseen',
            'lastip',
            'roles',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $event->setData(UserData::createFromEntity($event->getData()));
        });
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addId(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add('id', HiddenType::class, $options)
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addUserName(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['min' => 2, 'max' => 32]),
                        new UsersAssert\ExistingEntity([
                            'className'  => Entity\Users::class,
                            'fieldNames' => ['username'],
                            'message'    => Trans::__('page.edit-users.error.username-used'),
                        ]),
                    ],
                    'label' => Trans::__('page.edit-users.label.username'),
                    'attr'  => [
                        'placeholder' => Trans::__('page.edit-users.placeholder.username'),
                    ],
                ] + $options
            )
        ;
        $transformer = function ($username) {
            return mb_strtolower($username);
        };
        $builder->get('username')->addModelTransformer(new CallbackTransformer($transformer, $transformer));

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addDisplayName(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add(
                'displayname',
                TextType::class,
                [
                    'label' => Trans::__('page.edit-users.label.display-name'),
                    'attr'  => [
                        'placeholder' => Trans::__('page.edit-users.placeholder.displayname'),
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['min' => 3, 'max' => 32]),
                        new UsersAssert\ExistingEntity([
                            'className'  => Entity\Users::class,
                            'fieldNames' => ['displayname'],
                            'message'    => Trans::__('page.edit-users.error.displayname-used'),
                        ]),
                    ],
                ] + $options
            )
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param bool                 $checkMx
     *
     * @return AbstractUserType
     */
    protected function addEmail(FormBuilderInterface $builder, array $options = [], $checkMx = false)
    {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => Trans::__('page.edit-users.label.email'),
                    'attr'  => [
                        'placeholder' => Trans::__('page.edit-users.placeholder.email'),
                    ],
                    'constraints' => [
                        new Assert\Email([
                            'strict'  => false,
                            'message' => Trans::__('page.edit-users.error.email-invalid'),
                            'checkMX' => $checkMx,
                        ]),
                        new UsersAssert\ExistingEntity([
                            'className'  => Entity\Users::class,
                            'fieldNames' => ['email'],
                            'message'    => Trans::__('page.edit-users.error.email-used'),
                        ]),
                    ],
                    'required' => true,
                ] + $options
            )
        ;

        return $this;
    }

    /**
     * Regarding the autocomplete on the passwords:
     *
     * @see https://bugs.chromium.org/p/chromium/issues/detail?id=468153#c150
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addPassword(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add(
                'password',
                RepeatedType::class,
                [
                    'type'          => PasswordType::class,
                    'first_options' => [
                        'label' => Trans::__('page.edit-users.label.password'),
                        'attr'  => [
                            'placeholder'  => Trans::__('page.edit-users.placeholder.password'),
                            'autocomplete' => 'new-password',
                        ],
                        'constraints' => [
                            new Assert\Length([
                                'min'        => 6,
                                'max'        => 128,
                                'minMessage' => Trans::__('page.edit-users.error.password-short'),
                                'maxMessage' => Trans::__('page.edit-users.error.password-long'),
                            ]),
                            new UsersAssert\FieldContains([
                                'field'       => 'displayname',
                                'insensitive' => true,
                                'message'     => Trans::__('page.edit-users.error.password-different-displayname'),
                            ]),
                            new UsersAssert\FieldEquals([
                                'field'       => 'email',
                                'insensitive' => true,
                                'message'     => Trans::__('page.edit-users.error.password-different-email'),
                            ]),
                            new UsersAssert\FieldEquals([
                                'field'       => 'username',
                                'insensitive' => true,
                                'message'     => Trans::__('page.edit-users.error.password-different-username'),
                            ]),
                        ],
                    ],
                    'second_options' => [
                        'label' => Trans::__('page.edit-users.label.password-confirm'),
                        'attr'  => [
                            'placeholder'  => Trans::__('page.edit-users.placeholder.password-confirm'),
                            'autocomplete' => 'new-password',
                        ],
                    ],
                    'invalid_message' => Trans::__('page.edit-users.error.password-mismatch'),
                ] + $options
            )
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addEnabled(FormBuilderInterface $builder, array $options = [])
    {
        $enabledOptions = [
            Trans::__('page.edit-users.activated.yes') => 1,
            Trans::__('page.edit-users.activated.no')  => 0,
        ];

        $builder
            ->add(
                'enabled',
                ChoiceType::class,
                [
                    'choices_as_values' => true, // Can be removed when symfony/form:^3.0 is the minimum
                    'choices'           => $enabledOptions,
                    'expanded'          => false,
                    'constraints'       => new Assert\Choice(array_values($enabledOptions)),
                    'label'             => Trans::__('page.edit-users.label.user-enabled'),
                ] + $options
            )
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addLastSeen(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add(
                'lastseen',
                DateTimeType::class,
                [
                    'widget'   => 'single_text',
                    'format'   => 'yyyy-MM-dd HH:mm:ss',
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-seen'),
                ] + $options
            )
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addLastIp(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add(
                'lastip',
                TextType::class,
                [
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-ip'),
                ] + $options
            )
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addRoles(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'label'             => Trans::__('page.edit-users.label.assigned-roles'),
                    'multiple'          => true,
                    'required'          => false,
                    'expanded'          => true,
                    'choices_as_values' => true,
                    'choices'           => $options['choices'],
                    'choice_attr'       => function ($val) use ($options) {
                        return Bag::from($options['mutable'])->hasItem($val) ? [] : ['disabled' => 'disabled'];
                    },
                ]
            )
        ;

        return $this;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return AbstractUserType
     */
    protected function addSave(FormBuilderInterface $builder, array $options = [])
    {
        $builder
            ->add('save', SubmitType::class, $options)
        ;

        return $this;
    }
}
