<?php
namespace Bolt\Form;

use Bolt\EventListener\FormListener;
use Silex\Application;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Symfony Form subscriber extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FormEventTypeExtension extends AbstractTypeExtension
{
    /** @var \Silex\Application $app */
    protected $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FormListener($this->app));
    }

    /**
     * @inheritdoc
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
