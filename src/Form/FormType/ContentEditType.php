<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ContentType editing form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ContentEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => Trans::__('contenttypes.generic.save', ['%contenttype%' => $options['contenttype_name']]),
                ]
            )
            ->add(
                'save_return',
                SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.save-and-return-overview'),
                ]
            )
            ->add(
                'save_create',
                SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.save-and-create-new-record'),
                ]
            )
            ->add(
                'live_edit',
                ButtonType::class,
                [
                    'label' => Trans::__('general.phrase.live-edit'),
                ]
            )
            ->add(
                'preview',
                ButtonType::class,
                [
                    'label' => Trans::__('general.phrase.preview'),
                ]
            )
            ->add(
                'delete',
                SubmitType::class,
                [
                    'label' => Trans::__('contenttypes.generic.delete', ['%contenttype%' => $options['contenttype_name']]),
                    'attr'  => [
                        'style' => 'visibility: hidden;',
                    ],
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('contenttype_name');
    }
}
