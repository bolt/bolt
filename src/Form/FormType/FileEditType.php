<?php

namespace Bolt\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * File editing form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'contents',
                TextareaType::class,
                [
                    'constraints' => $options['contents_constraints'],
                ]
            )
            ->add(
                'revert',
                SubmitType::class,
                [
                    'label'    => Trans::__('page.edit-file.button.revert'),
                    'disabled' => !$options['write_allowed'],
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'label'    => Trans::__('page.edit-file.button.save'),
                    'disabled' => !$options['write_allowed'],
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('write_allowed')
            ->setDefined('contents_allow_empty')
            ->setAllowedValues('contents_allow_empty', [true, false])
            ->setDefault('contents_allow_empty', true)
        ;
        $resolver->setDefined('contents_constraints')
            ->setNormalizer('contents_constraints', function (Options $options, $value) {
                if ($options['contents_allow_empty']) {
                    return $value;
                }
                $value[] = new Assert\NotBlank();

                return $value;
            })
        ;
    }
}
