<?php
namespace Bolt\Form;

use Bolt\Config;
use Bolt\EventListener\FormListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Symfony Form subscriber extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FormEventTypeExtension extends AbstractTypeExtension
{
    /** @var SessionInterface $session */
    protected $session;
    /** @var \Bolt\Config $config */
    protected $config;
    /** @var Request $request */
    protected $request;
    /** @var NativeFileSessionHandler $handler */
    protected $handler;
    /** @var string $tokenName */
    protected $tokenName;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(SessionInterface $session, Request $request, Config $config, $handler, $tokenName)
    {
        $this->session   = $session;
        $this->request   = $request;
        $this->config    = $config;
        $this->handler   = $handler;
        $this->tokenName = $tokenName;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FormListener(
            $this->session,
            $this->request,
            $this->config,
            $this->handler,
            $this->tokenName
        ));
    }

    /**
     * @inheritdoc
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
