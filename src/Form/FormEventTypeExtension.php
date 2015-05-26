<?php
namespace Bolt\Form;

use Bolt\EventListener\FormListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Symfony Form subscriber extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FormEventTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FormListener());
    }

    public function getExtendedType()
    {
        return 'form';
    }
}
