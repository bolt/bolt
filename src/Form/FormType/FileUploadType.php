<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * File upload form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileUploadType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'select',
                FileType::class,
                [
                    'label'    => false,
                    'multiple' => true,
                    'attr'     => [
                        'data-filename-placement' => 'inside',
                        'title'                   => Trans::__('general.phrase.select-file'),
                        'accept'                  => $options['accept'],
                    ],
                ]
            )
            ->add(
                'upload',
                SubmitType::class,
                [
                    'label'    => Trans::__('general.phrase.upload-file'),
                    'disabled' => true,
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('accept');
    }
}
