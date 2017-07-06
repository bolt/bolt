<?php

namespace Bolt\Form\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Prefill form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PrefillType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'contenttypes',
                ChoiceType::class, [
                    'choices'  => $options['contenttypes'],
                    'multiple' => true,
                    'expanded' => true,
                    // Can be removed when symfony/form:^3.0 is the minimum
                    'choices_as_values' => true,
                ]
            )
            ->add('check_all', ButtonType::class)
            ->add('uncheck_all', ButtonType::class)
            ->add('submit', SubmitType::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('contenttypes');
    }
}
